<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * GET /api/cart
     * Get or create the authenticated user's cart with items and product details.
     */
    public function index(Request $request): JsonResponse
    {
        $cart = $this->getOrCreateCart($request->user()->id);

        return response()->json($this->formatCart($cart));
    }

    /**
     * POST /api/cart/items
     * Add an item to the cart (or increment quantity if already present).
     */
    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'cantidad'   => 'sometimes|integer|min:1',
        ]);

        $cantidad = $validated['cantidad'] ?? 1;
        $cart = $this->getOrCreateCart($request->user()->id);
        $product = Product::findOrFail($validated['product_id']);

        $existingItem = $cart->items()
            ->where('product_id', $product->id)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'cantidad' => $existingItem->cantidad + $cantidad,
            ]);
        } else {
            $cart->items()->create([
                'product_id'      => $product->id,
                'cantidad'        => $cantidad,
                'precio_unitario' => $product->pvp,
            ]);
        }

        return response()->json($this->formatCart($cart->fresh()));
    }

    /**
     * PUT /api/cart/items/{id}
     * Update the quantity of a cart item.
     */
    public function updateItem(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'cantidad' => 'required|integer|min:1',
        ]);

        $cartItem = CartItem::findOrFail($id);

        // Verify the item belongs to the authenticated user's cart
        $cart = Cart::where('user_id', $request->user()->id)->firstOrFail();

        if ($cartItem->cart_id !== $cart->id) {
            return response()->json(['message' => 'Este item no pertenece a tu carrito.'], 403);
        }

        $cartItem->update(['cantidad' => $validated['cantidad']]);

        return response()->json($this->formatCart($cart->fresh()));
    }

    /**
     * DELETE /api/cart/items/{id}
     * Remove an item from the cart.
     */
    public function removeItem(Request $request, int $id): JsonResponse
    {
        $cartItem = CartItem::findOrFail($id);

        $cart = Cart::where('user_id', $request->user()->id)->firstOrFail();

        if ($cartItem->cart_id !== $cart->id) {
            return response()->json(['message' => 'Este item no pertenece a tu carrito.'], 403);
        }

        $cartItem->delete();

        return response()->json($this->formatCart($cart->fresh()));
    }

    /**
     * POST /api/cart/merge
     * Merge localStorage cart items into the DB cart.
     */
    public function merge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items'              => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.cantidad'   => 'required|integer|min:1',
        ]);

        $cart = $this->getOrCreateCart($request->user()->id);

        foreach ($validated['items'] as $item) {
            $product = Product::findOrFail($item['product_id']);

            $existingItem = $cart->items()
                ->where('product_id', $product->id)
                ->first();

            if ($existingItem) {
                $existingItem->update([
                    'cantidad' => $existingItem->cantidad + $item['cantidad'],
                ]);
            } else {
                $cart->items()->create([
                    'product_id'      => $product->id,
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $product->pvp,
                ]);
            }
        }

        return response()->json($this->formatCart($cart->fresh()));
    }

    /**
     * Get or create a cart for the given user.
     */
    private function getOrCreateCart(int $userId): Cart
    {
        return Cart::firstOrCreate(['user_id' => $userId]);
    }

    /**
     * Format the cart response with items and product details.
     */
    private function formatCart(Cart $cart): array
    {
        $cart->load('items.product');

        $items = $cart->items->map(function (CartItem $item) {
            $product = $item->product;

            return [
                'id'              => $item->id,
                'product_id'      => $item->product_id,
                'nombre'          => $product?->descripcion,
                'slug'            => $product?->slug,
                'imagen_url'      => $product?->imagen_url,
                'pvp'             => $product?->pvp,
                'precio_unitario' => $item->precio_unitario,
                'cantidad'        => $item->cantidad,
                'subtotal'        => round($item->precio_unitario * $item->cantidad, 2),
            ];
        });

        return [
            'id'    => $cart->id,
            'items' => $items,
            'total' => round($items->sum('subtotal'), 2),
        ];
    }
}
