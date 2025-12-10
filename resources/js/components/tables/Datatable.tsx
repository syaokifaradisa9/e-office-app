import useMediaQuery from '../../helpers/mediaquery';
import DatatableFooter from './datatable/DatatableFooter';
import DatatableHeader from './datatable/DatatableHeader';
import DatatableMobileBody from './datatable/DatatableMobileBody';
import DatatableDesktopBody from './datatable/DatatableDesktopBody';

interface Column<T> {
    name?: string;
    header: string;
    headerClassName?: string;
    bodyClassname?: string;
    width?: string | number;
    render: (item: T) => React.ReactNode;
    footer?: React.ReactNode | ((data: T[]) => React.ReactNode);
    footerClassName?: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface DataTableProps<T> {
    dataTable: {
        data?: T[];
        links?: PaginationLink[];
        current_page?: number;
        last_page?: number;
        from?: number;
        to?: number;
        total?: number;
        [key: string]: unknown;
    };
    columns: Column<T>[];
    expandable?: (item: T) => React.ReactNode;
    onParamsChange: (e: { preventDefault?: () => void; target: { name: string; value: string } }) => void;
    onChangePage: (e: React.MouseEvent<HTMLAnchorElement>) => void;
    additionalHeaderElements?: React.ReactNode;
    cardItem?: (item: T) => React.ReactNode;
    limit: number;
    isLoading?: boolean;
    searchValue?: string;
    onSearchChange?: (e: { preventDefault: () => void; target: { name: string; value: string } }) => void;
    sortBy?: string;
    sortDirection?: 'asc' | 'desc';
    onHeaderClick?: (columnName: string) => void;
    SkeletonComponent?: React.ComponentType | null;
}

export default function DataTable<T extends { id: number | string }>({
    dataTable,
    columns,
    expandable,
    onParamsChange,
    onChangePage,
    additionalHeaderElements,
    cardItem,
    limit,
    isLoading = false,
    searchValue = '',
    onSearchChange,
    sortBy = 'created_at',
    sortDirection = 'desc',
    onHeaderClick,
    SkeletonComponent = null,
}: DataTableProps<T>) {
    const isMediumScreen = useMediaQuery('(min-width: 768px)');

    return (
        <div className="flex h-full flex-col">
            <DatatableHeader
                additionalHeaderElements={additionalHeaderElements}
                limit={limit}
                onParamsChange={onParamsChange}
                searchValue={searchValue}
                onSearchChange={onSearchChange}
            />
            <div className="relative mt-0 flex-grow md:mt-6">
                <div className="overflow-x-auto">
                    {isMediumScreen || !cardItem ? (
                        <DatatableDesktopBody
                            columns={columns}
                            dataTable={dataTable}
                            expandable={expandable}
                            isLoading={isLoading}
                            limit={limit}
                            sortBy={sortBy}
                            sortDirection={sortDirection}
                            onParamsChange={onParamsChange}
                            onHeaderClick={onHeaderClick}
                        />
                    ) : (
                        <DatatableMobileBody dataTable={dataTable} cardItem={cardItem} isLoading={isLoading} SkeletonComponent={SkeletonComponent} />
                    )}
                </div>
            </div>
            <DatatableFooter dataTable={dataTable} onChangePage={onChangePage} />
        </div>
    );
}
