"use client";

interface FilterChipsProps {
  /** Map of filter key → array of possible values */
  availableFilters: Record<string, string[]>;
  /** Currently selected filters: key → selected value */
  selectedFilters: Record<string, string>;
  onChange: (filters: Record<string, string>) => void;
}

export default function FilterChips({ availableFilters, selectedFilters, onChange }: FilterChipsProps) {
  const filterKeys = Object.keys(availableFilters).sort();

  if (filterKeys.length === 0) return null;

  const handleToggle = (key: string, value: string) => {
    const next = { ...selectedFilters };
    if (next[key] === value) {
      delete next[key];
    } else {
      next[key] = value;
    }
    onChange(next);
  };

  return (
    <div className="flex flex-col gap-3">
      {filterKeys.map((key) => (
        <div key={key}>
          <span className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">
            {key.replace(/_/g, " ")}
          </span>
          <div className="flex flex-wrap gap-2">
            {availableFilters[key].map((value) => {
              const isSelected = selectedFilters[key] === value;
              return (
                <button
                  key={value}
                  onClick={() => handleToggle(key, value)}
                  className={`px-3 py-1.5 rounded-full text-xs font-medium border transition-colors ${
                    isSelected
                      ? "bg-[#E8751A] text-white border-[#E8751A]"
                      : "bg-white text-[#333] border-gray-300 hover:border-[#E8751A] hover:text-[#E8751A]"
                  }`}
                >
                  {value}
                </button>
              );
            })}
          </div>
        </div>
      ))}
    </div>
  );
}
