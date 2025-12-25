import RootLayout from '@/components/layouts/RootLayout';
import DashboardStats from '@/components/dashboard/DashboardStats';
import InventoryDashboard from '@/components/dashboard/InventoryDashboard';

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
