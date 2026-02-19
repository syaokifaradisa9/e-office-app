import { Box } from 'lucide-react';
import SidebarLink from '@/components/layouts/SideBarLink';
import { useSidebarCollapse } from '@/components/layouts/SidebarContext';
import { TicketingPermission } from '../../types/permissions';
import CheckPermissions from '@/components/utils/CheckPermissions';

export default function TicketingSidebar() {
    const isCollapsed = useSidebarCollapse();

    const assetPermissions = [
        TicketingPermission.ViewAssetModelDivisi,
        TicketingPermission.ViewAllAssetModel,
        TicketingPermission.ManageAssetModel,
    ];

    return (
        <CheckPermissions permissions={assetPermissions}>
            <div className="mb-6 space-y-6">
                <div className="space-y-1">
                    <div className="py-2">
                        <h3 className={`text-[10px] font-bold tracking-wider text-slate-500 uppercase dark:text-slate-400 ${isCollapsed ? 'text-center' : ''}`}>Ticketing System</h3>
                    </div>
                    <CheckPermissions permissions={assetPermissions}>
                        <SidebarLink name="Asset Model" href="/ticketing/asset-models" icon={Box} />
                    </CheckPermissions>
                </div>
            </div>
        </CheckPermissions>
    );
}
