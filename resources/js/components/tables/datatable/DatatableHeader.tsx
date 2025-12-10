import { Search } from 'lucide-react';
import { useState, useEffect } from 'react';

interface DatatableHeaderProps {
    additionalHeaderElements?: React.ReactNode;
    onParamsChange: (e: { preventDefault: () => void; target: { name: string; value: string } }) => void;
    limit: number;
    searchValue?: string;
    onSearchChange?: (e: { preventDefault: () => void; target: { name: string; value: string } }) => void;
}

export default function DatatableHeader({
    additionalHeaderElements,
    onParamsChange,
    limit,
    searchValue = '',
    onSearchChange,
}: DatatableHeaderProps) {
    const [localSearch, setLocalSearch] = useState(searchValue || '');

    useEffect(() => {
        setLocalSearch(searchValue || '');
    }, [searchValue]);

    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (localSearch !== (searchValue || '')) {
                const event = {
                    preventDefault: () => { },
                    target: {
                        name: 'search',
                        value: localSearch,
                    },
                };
                if (onSearchChange) {
                    onSearchChange(event);
                } else {
                    onParamsChange(event);
                }
            }
        }, 500);

        return () => clearTimeout(timeoutId);
    }, [localSearch, searchValue, onSearchChange, onParamsChange]);

    return (
        <div className="hidden flex-col items-center justify-between gap-4 md:flex md:flex-row">
            {/* Limit selector - hidden on mobile */}
            <div className="hidden items-center gap-2 md:flex">
                <p className="text-sm text-slate-600 dark:text-slate-400">Tampilkan</p>
                <select
                    name="limit"
                    className="h-9 rounded-lg border border-gray-400 bg-white px-3 text-sm text-slate-700 transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300"
                    onChange={(e) =>
                        onParamsChange({
                            preventDefault: () => { },
                            target: { name: e.target.name, value: e.target.value },
                        })
                    }
                    value={limit}
                >
                    {[5, 10, 20, 50, 100].map((value) => (
                        <option key={`filter-page-limit-${value}`} value={value}>
                            {value}
                        </option>
                    ))}
                </select>
                <p className="text-sm text-slate-600 dark:text-slate-400">Per Halaman</p>
            </div>

            <div className="flex w-full flex-col gap-3 md:w-auto md:flex-row md:items-center">
                {/* Search bar - hidden on mobile (moved to TopBar) */}
                <div className="relative hidden md:block md:w-80">
                    <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <input
                        name="search"
                        type="text"
                        value={localSearch}
                        className="h-9 w-full rounded-lg border border-gray-400 bg-white pl-10 pr-4 text-sm text-slate-700 transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300"
                        placeholder="Cari data..."
                        onChange={(e) => setLocalSearch(e.target.value)}
                    />
                </div>
                {/* Additional elements - hidden on mobile (moved to MobileSearchBar) */}
                <div className="hidden items-center justify-center gap-2 md:flex">{additionalHeaderElements}</div>
            </div>
        </div>
    );
}
