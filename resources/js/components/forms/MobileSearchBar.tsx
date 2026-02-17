import { Search } from 'lucide-react';
import { useState, useEffect } from 'react';

interface MobileSearchBarProps {
    searchValue?: string;
    onSearchChange: (e: { preventDefault: () => void; target: { name: string; value: string } }) => void;
    placeholder?: string;
    actionButton?: React.ReactNode;
}

export default function MobileSearchBar({ searchValue = '', onSearchChange, placeholder = 'Cari data...', actionButton }: MobileSearchBarProps) {
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
                }
            }
        }, 500);

        return () => clearTimeout(timeoutId);
    }, [localSearch, searchValue, onSearchChange]);

    return (
        <div className="flex w-full items-center gap-4">
            <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <input
                    name="search"
                    type="text"
                    value={localSearch}
                    className="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-10 pr-4 text-sm text-slate-700 placeholder-gray-400 transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300"
                    placeholder={placeholder}
                    onChange={(e) => setLocalSearch(e.target.value)}
                />
            </div>
            {actionButton && <div className="flex-shrink-0">{actionButton}</div>}
        </div>
    );
}
