import * as React from "react";

import {
  Avatar,
  AvatarFallback,
  AvatarImage,
} from "@/components/ui/avatar";

import { Link } from "@inertiajs/react";

import {
  Sidebar,
  SidebarContent,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubItem,
  SidebarFooter,
} from "@/components/ui/sidebar";

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

import { Collapsible, CollapsibleTrigger, CollapsibleContent } from "@/components/ui/collapsible";
import { ChevronDown, ChevronUp, User2, ChartGantt, Building, Ticket, LineChart, LandPlot } from "lucide-react";

const data = {
  navMain: [
    {
      title: "Acara",
      icon: <ChartGantt />,
      href: "",
      items: [
        { title: "Daftar Acara", href: route("kursi.index") },
        { title: "Buat Acara", href: route("kursi.index") },
        { title: "Edit Acara", href: route("kursi.index") },
      ],
    },
    {
      title: "Venue",
      icon: <LandPlot />,
      href: "",
      items: [
        { title: "Daftar Venue", href: route("penjualan.index") },
        { title: "Sewa Venue", href: route("penjualan.index") },
        { title: "Pengaturan Venue", href: route("penjualan.index") },
      ],
    },
    {
      title: "Tiket",
      icon: <Ticket />,
      href: "",
      items: [
        { title: "Pengaturan Tiket", href: route("tiket.index") },
        { title: "Harga Tiket", href: route("tiket.index") },
        { title: "Verifikasi Tiket", href: route("tiket.index") },
      ],
    },
    {
      title: "Analitik",
      icon: <LineChart />,
      href: "",
      items: [
        { title: "Laporan Penjualan", href: route("tiket.index") },
        { title: "Riwayat Acara", href: route("tiket.index") },
      ],
    },
    {
      title: "Profil",
      icon: <Building />,
      href: "",
      items: [
        { title: "Pengaturan Profil", href: route("tiket.index") },
        { title: "Perbarui Profil", href: route("tiket.index") },
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
            <Collapsible defaultOpen className="group/collapsible" key={item.title}>
              <SidebarMenuItem>
                <CollapsibleTrigger asChild>
                  <SidebarMenuButton className="flex items-center text-[1.2vw]">
                    {item.icon}
                    {item.title}
                    <ChevronDown className="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-180" />
                  </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                  <SidebarMenuSub>
                    {item.items.map((subItem) => (
                      <SidebarMenuSubItem key={subItem.title}>
                        <Link href={subItem.href} className="block w-full text-sm text-gray-700 hover:text-gray-900">
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
                <Link href={route("profile.edit")} className="block">
                  <DropdownMenuItem>
                    <span>Profile</span>
                  </DropdownMenuItem>
                </Link>
                <DropdownMenuSeparator />
                <Link href={route("logout")} method="post" as="button" className="block">
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
