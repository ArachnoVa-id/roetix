import * as React from 'react';

import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';

import { Link } from '@inertiajs/react';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubItem,
} from '@/Components/ui/sidebar';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/Components/ui/collapsible';
import {
    Building,
    ChartGantt,
    ChevronDown,
    ChevronUp,
    LandPlot,
    LineChart,
    Ticket,
    User2,
} from 'lucide-react';

const data = {
    navMain: [
        {
            title: 'Acara',
            icon: <ChartGantt />,
            href: '',
            items: [
                { title: 'Daftar Acara', href: route('acara.index') },
                { title: 'Buat Acara', href: route('acara.create') },
                { title: 'Edit Acara', href: route('acara.edit') },
            ],
        },
        {
            title: 'Venue',
            icon: <LandPlot />,
            href: '',
            items: [
                { title: 'Daftar Venue', href: route('venue.index') },
                { title: 'Sewa Venue', href: route('venue.sewa') },
                { title: 'Pengaturan Venue', href: route('venue.pengaturan') },
            ],
        },
        {
            title: 'Tiket',
            icon: <Ticket />,
            href: '',
            items: [
                { title: 'Pengaturan Tiket', href: route('tiket.pengaturan') },
                { title: 'Harga Tiket', href: route('tiket.harga') },
                { title: 'Verifikasi Tiket', href: route('tiket.verifikasi') },
            ],
        },
        {
            title: 'Analitik',
            icon: <LineChart />,
            href: '',
            items: [
                { title: 'Laporan Penjualan', href: route('penjualan.index') },
                { title: 'Riwayat Acara', href: route('acara.riwayat') },
            ],
        },
        {
            title: 'Profil',
            icon: <Building />,
            href: '',
            items: [
                { title: 'Pengaturan Profil', href: route('profil.index') },
                { title: 'Perbarui Profil', href: route('profil.edit') },
            ],
        },
    ],
};

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
    return (
        <Sidebar {...props}>
            {/* Sidebar Header */}
            <SidebarHeader className="flex flex-row items-center">
                <Avatar>
                    {/* <AvatarImage src="https://github.com/shadcn.png" /> */}
                    <AvatarImage src="/images/novatix-logo.jpeg" />
                    <AvatarFallback>AV</AvatarFallback>
                </Avatar>
                <h1 className="text-lg font-semibold">Novatix-Arachnova</h1>
            </SidebarHeader>

            {/* Sidebar Content */}
            <SidebarContent>
                <SidebarMenu>
                    {data.navMain.map((item) => (
                        <Collapsible
                            defaultOpen
                            className="group/collapsible"
                            key={item.title}
                        >
                            <SidebarMenuItem>
                                <CollapsibleTrigger asChild>
                                    <SidebarMenuButton className="flex items-center text-[2.5vw] md:text-[1.2vw]">
                                        {item.icon}
                                        {item.title}
                                        <ChevronDown className="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-180" />
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        {item.items.map((subItem) => (
                                            <SidebarMenuSubItem
                                                key={subItem.title}
                                            >
                                                <Link
                                                    href={subItem.href}
                                                    className="block w-full text-sm text-gray-700 hover:text-gray-900"
                                                >
                                                    {subItem.title}
                                                </Link>
                                            </SidebarMenuSubItem>
                                        ))}
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </SidebarMenuItem>
                        </Collapsible>
                    ))}
                </SidebarMenu>
            </SidebarContent>

            {/* Sidebar Footer */}
            <SidebarFooter>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <SidebarMenuButton className="flex items-center">
                                    <User2 />
                                    <span className="ml-2">Username</span>
                                    <ChevronUp className="ml-auto" />
                                </SidebarMenuButton>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent
                                side="top"
                                className="w-[--radix-popper-anchor-width]"
                            >
                                <Link
                                    href={route('profile.edit')}
                                    className="block"
                                >
                                    <DropdownMenuItem>
                                        <span>Profile</span>
                                    </DropdownMenuItem>
                                </Link>
                                <DropdownMenuSeparator />
                                <Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                    className="block"
                                >
                                    <DropdownMenuItem>
                                        <span>Sign out</span>
                                    </DropdownMenuItem>
                                </Link>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarFooter>
        </Sidebar>
    );
}
