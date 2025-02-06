import React from "react";
import EodashboardLayout from "@/Layouts/EodashboardLayout";
import { Head } from "@inertiajs/react";

interface Props {
  title: string;
  subtitle: string;
}

export default function CreateAcara({ title, subtitle }: Props) {
  return (
    <EodashboardLayout title={title} subtitle={subtitle}>
      <Head title={`${title} `} />
      <div className="p-4">
        <h1 className="text-2xl font-bold">{title}</h1>
        <p className="mt-2 text-gray-600">Welcome to the {subtitle} page.</p>
        {/* Tambahkan konten khusus di sini */}
      </div>
    </EodashboardLayout>
  );
}
