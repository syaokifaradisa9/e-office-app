import RootLayout from '@/components/layouts/RootLayout';
import DashboardStats from '@/components/dashboard/DashboardStats';
import InventoryDashboard from '../../../Modules/Inventory/resources/assets/js/components/dashboard/InventoryDashboard';

export default function Dashboard() {
    return (
        <RootLayout title="Dashboard">
            <div className="space-y-8">
                <DashboardStats />
                <InventoryDashboard />
            </div>
        </RootLayout>
    );
}
