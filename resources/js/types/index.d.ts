import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Podcast {
    id: number;
    title: string;
    description?: string;
    image_url?: string;
    author?: string;
    last_refreshed_at?: string;
}

export interface Episode {
    id: number;
    title: string;
    description?: string;
    audio_url: string;
    thumbnail_url?: string;
    duration_formatted?: string;
    published_at: string;
    is_completed: boolean;
    podcast: Podcast;
}

export interface PaginationData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: any[];
}

export interface CompletionStats {
    total: number;
    completed: number;
}

export interface SelectedPodcast {
    id: number;
    title: string;
    description?: string;
    image_url?: string;
}
    
