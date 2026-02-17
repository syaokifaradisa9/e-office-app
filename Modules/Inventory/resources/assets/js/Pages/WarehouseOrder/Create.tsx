import { useForm, Link, usePage } from '@inertiajs/react';
import { Minus, Plus, ShoppingCart, Search, Package, X, ArrowLeft, ChevronLeft, ClipboardList } from 'lucide-react';
import React, { useState, useMemo, useRef, useEffect, useCallback } from 'react';


import Button from '@/components/buttons/Button';
import FormTextArea from '@/components/forms/FormTextArea';
import RootLayout from '@/components/layouts/RootLayout';

interface Category {
    id: number;
    name: string;
}

interface Item {
    id: number;
    name: string;
    stock: number;
    unit_of_measure: string;
    category_id: number;
    description: string;
    image_url?: string;
}

interface CartItem {
    item_id: number;
    quantity: number;
}

interface WarehouseOrder {
    id: number;
    description: string;
    notes: string;
    status: string;
    carts?: {
        item_id: number;
        quantity: number;
    }[];
    latest_reject?: {
        reason: string;
    };
}

interface Props {
    items: Item[];
    categories: Category[];
    warehouseOrder?: WarehouseOrder;
}

// ====== Memoized Item Card Component ======
const ItemCard = React.memo(function ItemCard({
    item, qty, failedImage, onQuantityChange, onManualChange, onStartPress, onStopPress, onImageFailed, onImageLoaded
}: {
    item: Item;
    qty: number;
    failedImage: boolean;
    onQuantityChange: (itemId: number, change: number) => void;
    onManualChange: (itemId: number, value: string) => void;
    onStartPress: (itemId: number, change: number) => void;
    onStopPress: () => void;
    onImageFailed: (itemId: number) => void;
    onImageLoaded: (itemId: number) => void;
}) {
    const isSelected = qty > 0;

    return (
        <div
            onClick={() => onQuantityChange(item.id, 1)}
            className={`group flex cursor-pointer select-none items-center gap-3 rounded-xl border p-3 transition-all duration-200 ${isSelected
                ? 'border-blue-200 bg-blue-50/50 shadow-sm dark:border-blue-500/40 dark:bg-blue-950/20'
                : 'border-gray-100 bg-white hover:border-gray-200 hover:shadow-sm dark:border-slate-700/50 dark:bg-slate-800 dark:hover:border-slate-600'
                }`}
        >
            <div className="size-12 shrink-0 overflow-hidden rounded-lg bg-gray-100 dark:bg-slate-700">
                {(failedImage || !item.image_url) ? (
                    <div className="flex h-full w-full items-center justify-center">
                        <Package className="size-5 text-gray-300 dark:text-slate-500" />
                    </div>
                ) : (
                    <img src={item.image_url} alt={item.name} className="h-full w-full object-cover"
                        onLoad={() => onImageLoaded(item.id)}
                        onError={() => onImageFailed(item.id)} />
                )}
            </div>
            <div className="min-w-0 flex-1">
                <h3 className="truncate text-sm font-semibold text-gray-800 dark:text-white" title={item.name}>{item.name}</h3>
                <div className="mt-1 flex items-center justify-between gap-2">
                    <p className="text-xs text-gray-400 dark:text-slate-500">
                        Stok: <span className={`font-semibold ${item.stock <= 0 ? 'text-red-500' : item.stock <= 10 ? 'text-amber-500' : 'text-emerald-500'}`}>{item.stock}</span> {item.unit_of_measure}
                    </p>
                    <div className="flex shrink-0 items-center gap-1" onClick={(e) => e.stopPropagation()}>
                        <button type="button" onPointerDown={() => onStartPress(item.id, -1)} onPointerUp={onStopPress} onPointerLeave={onStopPress}
                            className="flex size-7 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500 disabled:opacity-0 dark:hover:bg-red-900/20 dark:hover:text-red-400" disabled={!qty}>
                            <Minus className="size-3.5" />
                        </button>
                        <input type="number" min="0" max={item.stock} value={qty}
                            onChange={(e) => onManualChange(item.id, e.target.value)} onFocus={(e) => e.target.select()}
                            className="w-9 border-none bg-transparent p-0 text-center text-sm font-bold text-gray-700 focus:ring-0 [&::-webkit-inner-spin-button]:appearance-none dark:text-gray-200" />
                        <button type="button" onPointerDown={() => onStartPress(item.id, 1)} onPointerUp={onStopPress} onPointerLeave={onStopPress}
                            className="flex size-7 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-500 disabled:opacity-30 dark:hover:bg-blue-900/20 dark:hover:text-blue-400" disabled={qty >= item.stock}>
                            <Plus className="size-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
});

// ====== Memoized Cart Item Component ======
const CartItemCard = React.memo(function CartItemCard({
    cartItem, item, failedImage, onQuantityChange, onManualChange, onStartPress, onStopPress, onImageFailed
}: {
    cartItem: CartItem;
    item: Item;
    failedImage: boolean;
    onQuantityChange: (itemId: number, change: number) => void;
    onManualChange: (itemId: number, value: string) => void;
    onStartPress: (itemId: number, change: number) => void;
    onStopPress: () => void;
    onImageFailed: (itemId: number) => void;
}) {
    return (
        <div className="group flex items-center gap-3 border-b border-gray-100 py-3 last:border-0 dark:border-slate-700/50">
            <div className="size-14 shrink-0 overflow-hidden rounded-lg bg-gray-100 dark:bg-slate-700">
                {(failedImage || !item.image_url) ? (
                    <div className="flex h-full w-full items-center justify-center bg-gray-100 dark:bg-slate-700">
                        <span className="text-[10px] text-center font-medium text-gray-400 dark:text-gray-500 leading-tight">Tidak ada Foto</span>
                    </div>
                ) : (
                    <img src={item.image_url} alt={item.name} className="h-full w-full object-cover"
                        onError={() => onImageFailed(item.id)} />
                )}
            </div>
            <div className="flex min-w-0 flex-1 items-center justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <h3 className="text-sm font-semibold leading-tight text-gray-900 dark:text-white" title={item.name}>{item.name}</h3>
                    <p className="text-xs leading-tight text-gray-400 dark:text-slate-500">{item.unit_of_measure}</p>
                </div>
                <div className="flex shrink-0 items-center gap-2">
                    <div className="flex items-center gap-0">
                        <button onPointerDown={() => onStartPress(item.id, -1)} onPointerUp={onStopPress} onPointerLeave={onStopPress}
                            className="flex size-7 items-center justify-center rounded-full text-gray-500 transition-all hover:bg-gray-100 hover:text-red-600 disabled:opacity-30 dark:text-gray-400 dark:hover:bg-slate-800 dark:hover:text-red-400" disabled={cartItem.quantity <= 1}>
                            <Minus className="size-3.5" />
                        </button>
                        <input type="number" min="0" max={item.stock} value={cartItem.quantity}
                            onChange={(e) => onManualChange(item.id, e.target.value)} onFocus={(e) => e.target.select()}
                            className="w-8 border-none bg-transparent p-0 text-center text-sm font-bold text-gray-900 focus:ring-0 [&::-webkit-inner-spin-button]:appearance-none dark:text-white" />
                        <button onPointerDown={() => onStartPress(item.id, 1)} onPointerUp={onStopPress} onPointerLeave={onStopPress}
                            className="flex size-7 items-center justify-center rounded-full text-gray-500 transition-all hover:bg-gray-100 hover:text-blue-600 disabled:opacity-30 dark:text-gray-400 dark:hover:bg-slate-800 dark:hover:text-blue-400" disabled={cartItem.quantity >= item.stock}>
                            <Plus className="size-3.5" />
                        </button>
                    </div>
                    <button onClick={() => onQuantityChange(item.id, -cartItem.quantity)}
                        className="p-1 text-gray-400 transition-colors hover:text-red-500 md:opacity-0 md:group-hover:opacity-100">
                        <X className="size-4" />
                    </button>
                </div>
            </div>
        </div>
    );
});

export default function WarehouseOrderCreate({ items, categories, warehouseOrder }: Props) {
    const { loggeduser } = usePage<any>().props;
    const userDivisionName = loggeduser?.division_name;
    const { data, setData, post, put, processing, errors } = useForm({
        description: warehouseOrder?.description || '',
        notes: warehouseOrder?.notes || '',
        items: warehouseOrder?.carts?.map(cart => ({
            item_id: cart.item_id,
            quantity: cart.quantity
        })) || [] as CartItem[],
    });

    const [selectedCategory, setSelectedCategory] = useState<number | 'ALL'>('ALL');
    const [searchQuery, setSearchQuery] = useState('');
    const [quantities, setQuantities] = useState<Record<number, number>>(() => {
        const initialQuantities: Record<number, number> = {};
        if (warehouseOrder?.carts) {
            warehouseOrder.carts.forEach(cart => {
                initialQuantities[cart.item_id] = cart.quantity;
            });
        }
        return initialQuantities;
    });

    const [failedImages, setFailedImages] = useState<Record<number, boolean>>({});
    const [loadedImages, setLoadedImages] = useState<Record<number, boolean>>({});
    const [mobileStep, setMobileStep] = useState<'select' | 'review'>('select');

    // Pre-build items lookup map for O(1) access
    const itemsMap = useMemo(() => {
        const map = new Map<number, Item>();
        items.forEach(item => map.set(item.id, item));
        return map;
    }, [items]);

    // Sync quantities -> form data via useEffect (batched, no double-render)
    useEffect(() => {
        const newItems = Object.entries(quantities)
            .filter(([_, qty]) => qty > 0)
            .map(([itemId, qty]) => ({
                item_id: parseInt(itemId),
                quantity: qty
            }));
        setData('items', newItems);
    }, [quantities]);

    const filteredItems = useMemo(() => {
        const query = searchQuery.toLowerCase();
        return items.filter((item) => {
            const matchesCategory = selectedCategory === 'ALL' || item.category_id === selectedCategory;
            const matchesSearch = item.name.toLowerCase().includes(query);
            return matchesCategory && matchesSearch;
        });
    }, [items, selectedCategory, searchQuery]);

    const handleQuantityChange = useCallback((itemId: number, change: number) => {
        setQuantities((prev) => {
            const currentQty = prev[itemId] || 0;
            const newQty = Math.max(0, currentQty + change);
            const item = itemsMap.get(itemId);

            if (item && newQty > item.stock) return prev;
            if (newQty === currentQty) return prev;

            return { ...prev, [itemId]: newQty };
        });
    }, [itemsMap]);

    const handleManualQuantityChange = useCallback((itemId: number, value: string) => {
        const item = itemsMap.get(itemId);
        if (!item) return;

        let newQty = parseInt(value);
        if (isNaN(newQty)) newQty = 0;
        if (newQty < 0) newQty = 0;
        if (newQty > item.stock) newQty = item.stock;

        setQuantities((prev) => {
            if (prev[itemId] === newQty) return prev;
            return { ...prev, [itemId]: newQty };
        });
    }, [itemsMap]);

    const timerRef = useRef<NodeJS.Timeout | null>(null);
    const intervalRef = useRef<NodeJS.Timeout | null>(null);

    const startPress = useCallback((itemId: number, change: number) => {
        handleQuantityChange(itemId, change);
        timerRef.current = setTimeout(() => {
            intervalRef.current = setInterval(() => {
                handleQuantityChange(itemId, change);
            }, 100);
        }, 500);
    }, [handleQuantityChange]);

    const stopPress = useCallback(() => {
        if (timerRef.current) clearTimeout(timerRef.current);
        if (intervalRef.current) clearInterval(intervalRef.current);
    }, []);

    const handleImageFailed = useCallback((itemId: number) => {
        setFailedImages(prev => ({ ...prev, [itemId]: true }));
    }, []);

    const handleImageLoaded = useCallback((itemId: number) => {
        setLoadedImages(prev => ({ ...prev, [itemId]: true }));
    }, []);

    const handleSubmit = useCallback((e: React.FormEvent) => {
        e.preventDefault();
        if (warehouseOrder) {
            put(`/inventory/warehouse-orders/${warehouseOrder.id}/update`);
        } else {
            post('/inventory/warehouse-orders/store');
        }
    }, [warehouseOrder, put, post]);

    // Shared: Search Input
    const renderSearchInput = () => (
        <div className="group relative w-full">
            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <Search className="size-5 text-gray-400 transition-colors group-focus-within:text-blue-500" />
            </div>
            <input type="text" placeholder="Cari barang berdasarkan nama..."
                className="block w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-12 pr-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-slate-700/50 dark:bg-slate-800 dark:text-white dark:placeholder-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                value={searchQuery} onChange={(e) => setSearchQuery(e.target.value)} />
        </div>
    );

    // Shared: Category Filter
    const renderCategoryFilter = () => (
        <div className="flex overflow-x-auto [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-gray-200 [&::-webkit-scrollbar-thumb]:hover:bg-gray-300 [&::-webkit-scrollbar-track]:bg-transparent dark:[&::-webkit-scrollbar-thumb]:bg-slate-700 dark:[&::-webkit-scrollbar-thumb]:hover:bg-slate-600">
            <button onClick={() => setSelectedCategory('ALL')}
                className={`relative whitespace-nowrap border-b-2 px-6 py-3 text-xs font-medium transition-all ${selectedCategory === 'ALL'
                    ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-slate-700 dark:hover:text-gray-200'}`}>
                Semua Kategori
            </button>
            {categories.map((category) => (
                <button key={category.id} onClick={() => setSelectedCategory(category.id)}
                    className={`relative whitespace-nowrap border-b-2 px-6 py-3 text-xs font-medium transition-all ${selectedCategory === category.id
                        ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-slate-700 dark:hover:text-gray-200'}`}>
                    {category.name}
                </button>
            ))}
        </div>
    );

    // Shared: Empty Items
    const renderEmptyItems = () => (
        <div className="col-span-full flex flex-col items-center justify-center py-20 text-center">
            <div className="mb-4 rounded-full bg-gray-50 p-6 dark:bg-slate-800">
                <Search className="size-10 text-gray-300 dark:text-slate-600" />
            </div>
            <h3 className="mb-1 text-lg font-medium text-gray-900 dark:text-white">Tidak ada barang ditemukan</h3>
            <p className="mx-auto max-w-xs text-sm text-gray-500 dark:text-gray-400">Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
        </div>
    );

    // Shared: Form Footer
    const renderFormFooter = () => (
        <form onSubmit={handleSubmit} className="space-y-4">
            {warehouseOrder?.latest_reject && (
                <div className="rounded-lg border border-red-100 bg-red-50 p-3 dark:border-red-800/30 dark:bg-red-900/20">
                    <p className="mb-1 text-xs font-semibold text-red-600 dark:text-red-400">Alasan Penolakan:</p>
                    <p className="text-sm text-red-700 dark:text-red-300">{warehouseOrder.latest_reject.reason}</p>
                </div>
            )}
            <div className="space-y-3">
                <FormTextArea label="Deskripsi Penggunaan" name="description" value={data.description}
                    onChange={(e) => setData('description', e.target.value)} placeholder="Contoh: Untuk keperluan maintenance mesin A..."
                    error={errors.description} rows={2} />
                <FormTextArea label="Catatan Tambahan" name="notes" value={data.notes}
                    onChange={(e) => setData('notes', e.target.value)} placeholder="Opsional..."
                    error={errors.notes} rows={2} />
            </div>
            <Button label={warehouseOrder ? "Simpan Perubahan" : "Ajukan Permintaan"} type="submit" isLoading={processing}
                className="w-full justify-center py-2.5" disabled={data.items.length === 0} icon={<ShoppingCart className="size-4" />} />
        </form>
    );

    return (
        <RootLayout
            title={warehouseOrder ? "Edit Permintaan Barang" : "Buat Permintaan Barang"}
            forceCollapse={true}
            backPath="/inventory/warehouse-orders"
            onBackClick={mobileStep === 'review' ? () => setMobileStep('select') : undefined}
            mobileSearchBar={mobileStep === 'select' ? renderSearchInput() : undefined}
            desktopSearchBar={renderSearchInput()}
        >
            {/* ===== MOBILE LAYOUT ===== */}
            <div className="flex flex-col md:hidden" style={{ minHeight: 'calc(100vh - 6rem)' }}>
                {mobileStep === 'select' ? (
                    /* Step 1: Select Items */
                    <div className="fixed inset-x-0 bottom-0 top-[92px] z-20 flex flex-col overflow-hidden bg-[#F5FAFA] dark:bg-slate-900">
                        <div className="border-b border-gray-200 bg-white px-2 pt-2 dark:border-slate-700/50 dark:bg-slate-800">
                            {renderCategoryFilter()}
                        </div>

                        <div className="flex-1 overflow-y-auto px-4 pb-24 pt-4">
                            <div className="grid grid-cols-1 gap-2">
                                {filteredItems.map(item => (
                                    <ItemCard
                                        key={item.id}
                                        item={item}
                                        qty={quantities[item.id] || 0}
                                        failedImage={!!failedImages[item.id]}
                                        onQuantityChange={handleQuantityChange}
                                        onManualChange={handleManualQuantityChange}
                                        onStartPress={startPress}
                                        onStopPress={stopPress}
                                        onImageFailed={handleImageFailed}
                                        onImageLoaded={handleImageLoaded}
                                    />
                                ))}
                                {filteredItems.length === 0 && renderEmptyItems()}
                            </div>
                        </div>

                        {/* Bottom Review Bar - Solid & Not Transparent */}
                        <div className="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white p-4 pb-safe dark:border-slate-700 dark:bg-slate-800">
                            <button
                                type="button"
                                onClick={() => setMobileStep('review')}
                                disabled={data.items.length === 0}
                                className="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-primary/90 active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
                            >
                                <ClipboardList className="size-4" />
                                Review Permintaan
                                {data.items.length > 0 && (
                                    <span className="ml-1 rounded-full bg-white/20 px-2 py-0.5 text-xs font-bold">{data.items.length}</span>
                                )}
                            </button>
                        </div>
                    </div>
                ) : (
                    /* Step 2: Review Cart */
                    <div className="fixed inset-x-0 bottom-0 top-[52px] z-20 flex flex-col overflow-hidden bg-white dark:bg-slate-900">
                        {/* Scrollable Cart Items */}
                        <div className="flex-1 overflow-y-auto px-4 py-2">
                            {data.items.map(cartItem => {
                                const item = itemsMap.get(cartItem.item_id);
                                if (!item) return null;
                                return (
                                    <CartItemCard
                                        key={cartItem.item_id}
                                        cartItem={cartItem}
                                        item={item}
                                        failedImage={!!failedImages[item.id]}
                                        onQuantityChange={handleQuantityChange}
                                        onManualChange={handleManualQuantityChange}
                                        onStartPress={startPress}
                                        onStopPress={stopPress}
                                        onImageFailed={handleImageFailed}
                                    />
                                );
                            })}
                        </div>

                        {/* Fixed Form Footer */}
                        <div className="shrink-0 border-t border-gray-100 bg-white p-4 dark:border-slate-700/50 dark:bg-slate-900">
                            <form onSubmit={handleSubmit} className="space-y-4">
                                {warehouseOrder?.latest_reject && (
                                    <div className="rounded-lg border border-red-100 bg-red-50 p-3 dark:border-red-800/30 dark:bg-red-900/20">
                                        <p className="mb-1 text-xs font-semibold text-red-600 dark:text-red-400">Alasan Penolakan:</p>
                                        <p className="text-sm text-red-700 dark:text-red-300">{warehouseOrder.latest_reject.reason}</p>
                                    </div>
                                )}
                                <div className="space-y-3">
                                    <FormTextArea label="Deskripsi Penggunaan" name="description" value={data.description}
                                        onChange={(e) => setData('description', e.target.value)} placeholder="Contoh: Untuk keperluan maintenance mesin A..."
                                        error={errors.description} rows={2} />
                                    <FormTextArea label="Catatan Tambahan" name="notes" value={data.notes}
                                        onChange={(e) => setData('notes', e.target.value)} placeholder="Opsional..."
                                        error={errors.notes} rows={2} />
                                </div>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={() => setMobileStep('select')}
                                        className="flex size-11 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-slate-500 transition-all hover:bg-gray-50 active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700"
                                    >
                                        <ChevronLeft className="size-5" />
                                    </button>
                                    <Button label={warehouseOrder ? "Simpan Perubahan" : "Ajukan Permintaan"} type="submit" isLoading={processing}
                                        className="w-full justify-center py-2.5" disabled={data.items.length === 0} icon={<ShoppingCart className="size-4" />} />
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>

            {/* ===== DESKTOP LAYOUT ===== */}
            <div className="hidden md:flex h-[calc(100vh-8rem)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                {/* Left Side - Items Area */}
                <div className="relative flex h-full flex-1 flex-col gap-4 overflow-hidden pt-6">
                    <div className="flex flex-col gap-4 px-6">
                        <div className="flex items-center gap-3">
                            <Link href="/inventory/warehouse-orders"
                                className="flex size-8 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-700">
                                <ArrowLeft className="size-5" />
                            </Link>
                            <div>
                                <h1 className="text-lg font-bold text-slate-900 dark:text-white">
                                    {warehouseOrder ? "Edit Permintaan Barang" : "Buat Permintaan Barang"}
                                    {userDivisionName && <span className="text-primary ml-1">Divisi {userDivisionName}</span>}
                                </h1>
                                <p className="text-sm text-slate-500 dark:text-slate-400">Pilih barang-barang di bawah ini untuk disimpan ke dalam keranjang permintaan</p>
                            </div>
                        </div>
                        <div className="mt-2">
                            {renderCategoryFilter()}
                        </div>
                    </div>
                    <div className="flex-1 overflow-y-auto px-6 pb-6 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-gray-200 [&::-webkit-scrollbar-thumb]:hover:bg-gray-300 [&::-webkit-scrollbar-track]:bg-transparent dark:[&::-webkit-scrollbar-thumb]:bg-slate-700 dark:[&::-webkit-scrollbar-thumb]:hover:bg-slate-600">
                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                            {filteredItems.map(item => (
                                <ItemCard
                                    key={item.id}
                                    item={item}
                                    qty={quantities[item.id] || 0}
                                    failedImage={!!failedImages[item.id]}
                                    onQuantityChange={handleQuantityChange}
                                    onManualChange={handleManualQuantityChange}
                                    onStartPress={startPress}
                                    onStopPress={stopPress}
                                    onImageFailed={handleImageFailed}
                                    onImageLoaded={handleImageLoaded}
                                />
                            ))}
                            {filteredItems.length === 0 && renderEmptyItems()}
                        </div>
                    </div>
                </div>

                {/* Right Side - Fixed Cart */}
                <div className="relative z-20 flex h-full w-[400px] flex-col border-l border-gray-100 bg-white dark:border-slate-700/50 dark:bg-slate-800">
                    <div className="flex shrink-0 items-center justify-between border-b border-gray-100 bg-white px-5 py-3 dark:border-slate-700/50 dark:bg-slate-800">
                        <div className="flex items-center gap-2">
                            <ShoppingCart className="size-4 text-gray-700 dark:text-gray-300" />
                            <h2 className="text-sm font-semibold text-gray-900 dark:text-white">Keranjang</h2>
                        </div>
                        <span className="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600 dark:bg-slate-700 dark:text-gray-300">{data.items.length}</span>
                    </div>
                    <div className="flex-1 overflow-y-auto bg-gray-50/30 px-4 py-2 [&::-webkit-scrollbar]:w-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-gray-200 [&::-webkit-scrollbar-thumb]:hover:bg-gray-300 [&::-webkit-scrollbar-track]:bg-transparent dark:bg-slate-900/20 dark:[&::-webkit-scrollbar-thumb]:bg-slate-700 dark:[&::-webkit-scrollbar-thumb]:hover:bg-slate-600">
                        {data.items.length === 0 ? (
                            <div className="flex h-full flex-col items-center justify-center text-center opacity-40">
                                <ShoppingCart className="mb-4 size-16 text-gray-300 dark:text-slate-600" />
                                <p className="font-medium text-gray-900 dark:text-white">Keranjang Kosong</p>
                                <p className="text-sm text-gray-500">Belum ada barang dipilih</p>
                            </div>
                        ) : data.items.map(cartItem => {
                            const item = itemsMap.get(cartItem.item_id);
                            if (!item) return null;
                            return (
                                <CartItemCard
                                    key={cartItem.item_id}
                                    cartItem={cartItem}
                                    item={item}
                                    failedImage={!!failedImages[item.id]}
                                    onQuantityChange={handleQuantityChange}
                                    onManualChange={handleManualQuantityChange}
                                    onStartPress={startPress}
                                    onStopPress={stopPress}
                                    onImageFailed={handleImageFailed}
                                />
                            );
                        })}
                    </div>
                    <div className="space-y-4 border-t border-gray-100 bg-white p-6 dark:border-slate-700/50 dark:bg-slate-800">
                        {renderFormFooter()}
                    </div>
                </div>
            </div>
        </RootLayout>
    );
}

