import RootLayout from '@/components/layouts/RootLayout';
import DashboardStats from '@/components/dashboard/DashboardStats';
import UnifiedModuleDashboard from '@/components/dashboard/UnifiedModuleDashboard';

export default function Dashboard() {
    return (
        <RootLayout title="Dashboard">
            <div className="space-y-8">
                <DashboardStats />
                <UnifiedModuleDashboard />
            </div>
        </RootLayout>
    );
}
