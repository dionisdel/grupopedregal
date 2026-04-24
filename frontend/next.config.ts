import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  trailingSlash: true,
  images: {
    unoptimized: true,
    remotePatterns: [
      {
        protocol: "http",
        hostname: "localhost",
        port: "8000",
      },
      {
        protocol: "http",
        hostname: "127.0.0.1",
        port: "8002",
      },
      {
        protocol: "https",
        hostname: "www.grupopedregal.es",
      },
      {
        protocol: "http",
        hostname: "www.grupopedregal.es",
      },
    ],
  },
};

export default nextConfig;
