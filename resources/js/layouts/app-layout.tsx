import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children }: AppLayoutProps) => (
    <div className="flex min-h-screen flex-col bg-white">
        <main className="flex-1">
            {children}
        </main>
    </div>
);
