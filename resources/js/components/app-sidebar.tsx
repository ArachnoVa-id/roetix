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
import { ChevronDown, ChevronUp, User2 } from "lucide-react";

const data = {
  navMain: [
    {
      title: "Penjualan",
      href: "",
      items: [
        { title: "Terbeli", href: route("penjualan.index") },
        // { title: "Opsi 2", href: route("penjualan.opsi2") },
        // { title: "Opsi 3", href: route("penjualan.opsi3") },
        // { title: "Opsi 4", href: route("penjualan.opsi4") },
      ],
    },
    {
      title: "Kursi",
      href: "",
      items: [
        { title: "Overview", href: route("kursi.index") },
        // { title: "Atur Poisi", href: route("kursi.aturPoisi") },
        // { title: "Opsi 3", href: route("kursi.opsi3") },
        // { title: "Opsi 4", href: route("kursi.opsi4") },
      ],
    },
    {
      title: "Tiket",
      href: "",
      items: [
        { title: "Harga Tiket", href: route("tiket.index") },
        // { title: "Scan Tiket", href: route("tiket.scanTiket") },
        // { title: "Opsi 3", href: route("tiket.opsi3") },
        // { title: "Opsi 4", href: route("tiket.opsi4") },
      ],
    },
    // {
    //   title: "Lain Lain",
    //   href: "",
    //   items: [
    //     { title: "Pengumuman", href: route("lainLain.index") },
    //     { title: "Time Line", href: route("lainLain.timeline") },
    //     { title: "Opsi 3", href: route("lainLain.opsi3") },
    //     { title: "Opsi 4", href: route("lainLain.opsi4") },
    //   ],
    // },
  ],
};

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
  return (
    <Sidebar {...props}>
      {/* Sidebar Header */}
      <SidebarHeader className="flex flex-row items-center">
        <Avatar>
          <AvatarImage src="https://github.com/shadcn.png" />
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
