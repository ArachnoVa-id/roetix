import React from "react";
import EodashboardLayout from "@/Layouts/EodashboardLayout";
import { Head, Link } from "@inertiajs/react";
import { Button } from "@/components/ui/button"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { BookUser, Instagram, Mail, MessageCircle } from 'lucide-react';

interface Venue {
  venue_id: string;
  name: string;
  location: string;
  capacity: number;
  contact_info: string;
  contactinfo: {
    contactinfo_id: string;
    contact_id: string;
    phone_number: string;
    email: string;
    whatsapp_number: string;
    instagram: string;
    created_at: string;
    updated_at: string;
  };
  status: string;
  created_at: string;
  updated_at: string;
}

interface Props {
  venues: Venue[];
  title: string;
  subtitle: string;
}

export default function Index({ venues, title, subtitle }: Props) {
  return (
    <EodashboardLayout title={title} subtitle={subtitle}>
      <Head title={`${title}`} />
      <div className="p-4">
        <h1 className="text-2xl font-bold">{title}</h1>
        <p className="mt-2 text-gray-600">Welcome to the {subtitle} page.</p>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>name</TableHead>
              <TableHead>location</TableHead>
              <TableHead>capacity</TableHead>
              <TableHead>status</TableHead>
              <TableHead>whatsapp</TableHead>
              <TableHead>other contacts</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {venues.map((item) => (
              <TableRow key={item.venue_id}>
                <Link href={route("acara.index")}>
                  <TableCell>{item.name}</TableCell>
                </Link>
                <TableCell>{item.location}</TableCell>
                <TableCell>{item.capacity}</TableCell>
                <TableCell>{item.status}</TableCell>
                <TableCell>{item.contactinfo.whatsapp_number}</TableCell>
                <TableCell>
                  <Popover>
                    <PopoverTrigger>
                      <Button variant="outline">Contacts</Button>
                    </PopoverTrigger>
                    <PopoverContent className="flex flex-col md:gap-[1vw] gap-[0.5vw]">
                      <Button 
                        variant='outline'
                        className="bg-gradient-to-r from-[#f0c434] via-[#ffef0e] to-[rgb(255,217,0)] text-white px-4 py-2 flex items-center gap-2 rounded-lg shadow-lg hover:opacity-90 transition-all duration-300"
                      >
                        <BookUser size={100} />
                        {item.contactinfo.phone_number}
                      </Button>
                      <Button 
                        variant='outline'
                        className="bg-gradient-to-r from-[#25D366] via-[#29c514] to-[#12e683] text-white px-4 py-2 flex items-center gap-2 rounded-lg shadow-lg hover:opacity-90 transition-all duration-300"
                      >
                        <MessageCircle size={100} />
                        {item.contactinfo.whatsapp_number}
                      </Button>
                      <Button
                        variant="outline"
                        className="bg-gradient-to-r from-[#d93025] via-[#ea4330] to-[#ea4335] text-white px-4 py-2 flex items-center gap-2 rounded-lg shadow-lg hover:opacity-90 transition-all duration-300"
                      >
                        <Mail size={24} />
                        {item.contactinfo.email}
                      </Button>
                      <Button 
                        variant='outline'
                        className="bg-gradient-to-r from-[#feda75] via-[#d62976] to-[#4f5bd5] text-white px-4 py-2 flex items-center gap-2 rounded-lg shadow-lg hover:opacity-90 transition-all duration-300"
                      >
                        <Instagram size={100} />
                        {item.contactinfo.instagram}
                      </Button>
                    </PopoverContent>
                  </Popover>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>
    </EodashboardLayout>
  );
}
