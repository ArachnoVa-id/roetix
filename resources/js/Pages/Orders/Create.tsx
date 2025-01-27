import React, { useState } from "react";
// import { Inertia } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select } from "@/components/ui/select";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

const Create: React.FC = () => {
  const [formData, setFormData] = useState({
    user_id: "",
    ticket_id: "",
    order_date: "",
    total_price: "",
    status: "pending",
  });

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Inertia.post("/orders", formData);
  };

  return (
    <div className="container mx-auto p-6">
      <h1 className="text-2xl font-bold mb-4">Buat Order Baru</h1>

      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <Label htmlFor="user_id">User ID</Label>
          <Input
            id="user_id"
            name="user_id"
            value={formData.user_id}
            onChange={handleChange}
            required
          />
        </div>

        <div>
          <Label htmlFor="ticket_id">Ticket ID</Label>
          <Input
            id="ticket_id"
            name="ticket_id"
            value={formData.ticket_id}
            onChange={handleChange}
            required
          />
        </div>

        <div>
          <Label htmlFor="order_date">Order Date</Label>
          <Input
            id="order_date"
            name="order_date"
            type="date"
            value={formData.order_date}
            onChange={handleChange}
            required
          />
        </div>

        <div>
          <Label htmlFor="total_price">Total Price</Label>
          <Input
            id="total_price"
            name="total_price"
            type="number"
            value={formData.total_price}
            onChange={handleChange}
            required
          />
        </div>

        <div>
          <Label htmlFor="status">Status</Label>
          <Select
            name="status"
            value={formData.status}
            // onValueChange={handleChange}
            required
          >
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </Select>
        </div>

        <Button type="submit">Simpan</Button>
      </form>
    </div>
  );
};

export default Create;
