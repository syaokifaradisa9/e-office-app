import { ReactNode } from 'react';

interface TableHeader {
    label: string;
    className?: string;
    isHidden?: boolean;
}

interface TableColumn<T> {
    render: (item: T, index: number) => ReactNode;
    className?: string;
    isHidden?: boolean;
}

interface TableFooter {
    label: string | ReactNode;
    className?: string;
    colSpan?: number;
}

interface GeneralTableProps<T> {
    title?: string;
    headers: TableHeader[];
    items: T[];
    columns: TableColumn<T>[];
    footers?: TableFooter[];
    type?: 'regular' | 'small';
    className?: string;
}

export default function GeneralTable<T>({
    title = '',
    headers,
    items,
    columns,
    footers,
    type = 'regular',
    className = '',
}: GeneralTableProps<T>) {
    return (
        <div className={className}>
            {title && <label className="text-sm font-medium text-gray-700 dark:text-slate-300">{title}</label>}
            <table className="mt-3 w-full">
                <thead className="bg-gray-100 dark:bg-slate-700/90">
                    <tr>
                        {headers.map((item, index) =>
                            item.isHidden ? null : (
                                <th
                                    key={`table-header-${index}`}
                                    className={`${item.className || ''} px-4 py-3 text-left text-xs font-medium text-gray-700 md:text-sm dark:text-slate-300`}
                                >
                                    {item.label}
                                </th>
                            ),
                        )}
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-300/80 dark:divide-slate-700">
                    {items.map((item, rowIndex) => (
                        <tr key={`table-body-${rowIndex}`} className="hover:bg-gray-50 dark:hover:bg-slate-700/80">
                            {columns.map((column, columnIndex) =>
                                column.isHidden ? null : (
                                    <td
                                        key={`table-col-${columnIndex}`}
                                        className={`${column.className || ''} ${type === 'regular' ? 'py-3' : ''} ${type === 'small' ? 'py-2' : ''} px-4 text-xs text-gray-700 md:text-sm dark:text-slate-300`}
                                    >
                                        {column.render(item, rowIndex)}
                                    </td>
                                ),
                            )}
                        </tr>
                    ))}
                </tbody>
                {footers && (
                    <tfoot>
                        <tr>
                            {footers.map((item, index) => (
                                <th
                                    key={`table-footer-${index}`}
                                    colSpan={item.colSpan ?? 1}
                                    className={`${item.className || ''} px-4 py-3 text-left text-xs font-medium text-gray-700 md:text-sm dark:text-slate-300`}
                                >
                                    {item.label}
                                </th>
                            ))}
                        </tr>
                    </tfoot>
                )}
            </table>
        </div>
    );
}
