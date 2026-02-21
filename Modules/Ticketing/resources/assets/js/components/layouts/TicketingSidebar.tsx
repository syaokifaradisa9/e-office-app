import { Box, Calendar, ClipboardCheck } from 'lucide-react';
import SidebarLink from '@/components/layouts/SideBarLink';
import { useSidebarCollapse } from '@/components/layouts/SidebarContext';
import { TicketingPermission } from '../../types/permissions';
import CheckPermissions from '@/components/utils/CheckPermissions';

export default function TicketingSidebar() {
    const isCollapsed = useSidebarCollapse();

    return (
        <div className="mb-6 space-y-6">
            <div className="space-y-1">
                <div className="py-2">
                    <h3 className={`text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400 ${isCollapsed ? 'text-center' : ''}`}>Modul Ticketing</h3>
                </div>

                <CheckPermissions permissions={[
                    TicketingPermission.ViewAssetCategoryDivisi,
                    TicketingPermission.ViewAllAssetCategory,
                    TicketingPermission.ManageAssetCategory,
                ]}>
                    <SidebarLink name="Kategori Asset" href="/ticketing/asset-categories" icon={Box} />
                </CheckPermissions>

                <CheckPermissions permissions={[
                    TicketingPermission.ViewPersonalAsset,
                    TicketingPermission.ViewDivisionAsset,
                    TicketingPermission.ViewAllAsset,
                    TicketingPermission.ManageAsset,
                ]}>
                    <SidebarLink name="Asset" href="/ticketing/assets" icon={Box} />
                </CheckPermissions>
            </div>

            <CheckPermissions permissions={[
                TicketingPermission.ViewDivisionMaintenance,
                TicketingPermission.ViewAllMaintenance,
                TicketingPermission.ManageAsset,
            ]}>
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className={`text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400 ${isCollapsed ? 'text-center' : ''}`}>Modul Maintenance</h3>
                    </div>
                    <CheckPermissions permissions={[
                        TicketingPermission.ViewDivisionMaintenance,
                        TicketingPermission.ViewAllMaintenance,
                        TicketingPermission.ManageAsset,
                    ]}>
                        <SidebarLink name="Jadwal Maintenance" href="/ticketing/maintenances" icon={Calendar} />
                    </CheckPermissions>
                </div>
            </CheckPermissions>
        </div>
    );
}
