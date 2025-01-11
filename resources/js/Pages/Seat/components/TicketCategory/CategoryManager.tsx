import axios from 'axios';
import React, { useEffect, useState } from 'react';
import { TicketCategory } from '../../types/ticketCategory';

interface CategoryManagerProps {
    eventId: string;
    onUpdate?: () => void;
}

export const CategoryManager: React.FC<CategoryManagerProps> = ({
    eventId,
    onUpdate,
}) => {
    const [categories, setCategories] = useState<TicketCategory[]>([]);
    const [newCategory, setNewCategory] = useState({
        name: '',
        color: '#000000',
        price: '',
    });
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        fetchCategories();
    }, [eventId]);

    const fetchCategories = async () => {
        try {
            const response = await axios.get(
                `/api/events/${eventId}/ticket-categories`,
            );
            setCategories(response.data);
        } catch (error) {
            console.error('Error fetching categories:', error);
        }
    };

    const handleAddCategory = async () => {
        try {
            setLoading(true);
            await axios.post(`/api/events/${eventId}/ticket-categories`, {
                name: newCategory.name,
                color: newCategory.color,
                timebound_prices: [
                    {
                        start_date: new Date().toISOString(),
                        end_date: new Date(
                            Date.now() + 30 * 24 * 60 * 60 * 1000,
                        ).toISOString(),
                        price: parseFloat(newCategory.price),
                    },
                ],
            });

            setNewCategory({ name: '', color: '#000000', price: '' });
            fetchCategories();
            onUpdate?.();
        } catch (error) {
            console.error('Error adding category:', error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="rounded-lg bg-white p-6 shadow">
            <h2 className="mb-4 text-xl font-bold">Ticket Categories</h2>

            {/* Add New Category Form */}
            <div className="mb-6 grid grid-cols-4 gap-4">
                <input
                    type="text"
                    placeholder="Category Name"
                    value={newCategory.name}
                    onChange={(e) =>
                        setNewCategory((prev) => ({
                            ...prev,
                            name: e.target.value,
                        }))
                    }
                    className="rounded border px-3 py-2"
                />
                <input
                    type="color"
                    value={newCategory.color}
                    onChange={(e) =>
                        setNewCategory((prev) => ({
                            ...prev,
                            color: e.target.value,
                        }))
                    }
                    className="rounded border px-3 py-2"
                />
                <input
                    type="number"
                    placeholder="Price"
                    value={newCategory.price}
                    onChange={(e) =>
                        setNewCategory((prev) => ({
                            ...prev,
                            price: e.target.value,
                        }))
                    }
                    className="rounded border px-3 py-2"
                />
                <button
                    onClick={handleAddCategory}
                    disabled={loading}
                    className="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 disabled:bg-gray-400"
                >
                    {loading ? 'Adding...' : 'Add Category'}
                </button>
            </div>

            {/* Categories List */}
            <div className="space-y-4">
                {categories.map((category) => (
                    <CategoryItem
                        key={category.ticket_category_id}
                        category={category}
                        onUpdate={fetchCategories}
                    />
                ))}
            </div>
        </div>
    );
};

// CategoryItem component for managing individual categories
interface CategoryItemProps {
    category: TicketCategory;
    onUpdate: () => void;
}

const CategoryItem: React.FC<CategoryItemProps> = ({ category, onUpdate }) => {
    const [editing, setEditing] = useState(false);
    const [priceModalOpen, setPriceModalOpen] = useState(false);
    const [editedCategory, setEditedCategory] = useState(category);

    const handleUpdate = async () => {
        try {
            await axios.put(
                `/api/ticket-categories/${category.ticket_category_id}`,
                {
                    name: editedCategory.name,
                    color: editedCategory.color,
                },
            );
            setEditing(false);
            onUpdate();
        } catch (error) {
            console.error('Error updating category:', error);
        }
    };

    return (
        <div className="rounded border p-4">
            {editing ? (
                <div className="space-y-2">
                    <input
                        type="text"
                        value={editedCategory.name}
                        onChange={(e) =>
                            setEditedCategory((prev) => ({
                                ...prev,
                                name: e.target.value,
                            }))
                        }
                        className="w-full rounded border px-3 py-2"
                    />
                    <input
                        type="color"
                        value={editedCategory.color}
                        onChange={(e) =>
                            setEditedCategory((prev) => ({
                                ...prev,
                                color: e.target.value,
                            }))
                        }
                        className="rounded border p-1"
                    />
                    <div className="flex gap-2">
                        <button
                            onClick={handleUpdate}
                            className="rounded bg-green-500 px-3 py-1 text-white hover:bg-green-600"
                        >
                            Save
                        </button>
                        <button
                            onClick={() => setEditing(false)}
                            className="rounded bg-gray-500 px-3 py-1 text-white hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            ) : (
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div
                            className="h-6 w-6 rounded"
                            style={{ backgroundColor: category.color }}
                        />
                        <span className="font-medium">{category.name}</span>
                        {category.current_price && (
                            <span className="text-gray-600">
                                ${category.current_price.price}
                            </span>
                        )}
                    </div>
                    <div className="flex gap-2">
                        <button
                            onClick={() => setEditing(true)}
                            className="rounded bg-blue-500 px-3 py-1 text-white hover:bg-blue-600"
                        >
                            Edit
                        </button>
                        <button
                            onClick={() => setPriceModalOpen(true)}
                            className="rounded bg-purple-500 px-3 py-1 text-white hover:bg-purple-600"
                        >
                            Manage Prices
                        </button>
                    </div>
                </div>
            )}

            {/* Price Management Modal */}
            {priceModalOpen && (
                <TimeboundPriceModal
                    category={category}
                    onClose={() => setPriceModalOpen(false)}
                    onUpdate={onUpdate}
                />
            )}
        </div>
    );
};

// TimeboundPriceModal for managing pricing periods
interface TimeboundPriceModalProps {
    category: TicketCategory;
    onClose: () => void;
    onUpdate: () => void;
}

const TimeboundPriceModal: React.FC<TimeboundPriceModalProps> = ({
    category,
    onClose,
    onUpdate,
}) => {
    const [newPrice, setNewPrice] = useState({
        start_date: '',
        end_date: '',
        price: '',
    });

    const handleAddPrice = async () => {
        try {
            await axios.post(
                `/api/ticket-categories/${category.ticket_category_id}/prices`,
                newPrice,
            );
            onUpdate();
            onClose();
        } catch (error) {
            console.error('Error adding timebound price:', error);
        }
    };

    return (
        <div className="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
            <div className="w-full max-w-md rounded-lg bg-white p-6">
                <h3 className="mb-4 text-lg font-semibold">
                    Manage Prices - {category.name}
                </h3>

                <div className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium">
                            Start Date
                        </label>
                        <input
                            type="datetime-local"
                            value={newPrice.start_date}
                            onChange={(e) =>
                                setNewPrice((prev) => ({
                                    ...prev,
                                    start_date: e.target.value,
                                }))
                            }
                            className="mt-1 w-full rounded border px-3 py-2"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium">
                            End Date
                        </label>
                        <input
                            type="datetime-local"
                            value={newPrice.end_date}
                            onChange={(e) =>
                                setNewPrice((prev) => ({
                                    ...prev,
                                    end_date: e.target.value,
                                }))
                            }
                            className="mt-1 w-full rounded border px-3 py-2"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium">
                            Price
                        </label>
                        <input
                            type="number"
                            value={newPrice.price}
                            onChange={(e) =>
                                setNewPrice((prev) => ({
                                    ...prev,
                                    price: e.target.value,
                                }))
                            }
                            className="mt-1 w-full rounded border px-3 py-2"
                        />
                    </div>

                    <div className="flex justify-end gap-2">
                        <button
                            onClick={onClose}
                            className="rounded border px-4 py-2 hover:bg-gray-100"
                        >
                            Cancel
                        </button>
                        <button
                            onClick={handleAddPrice}
                            className="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600"
                        >
                            Add Price Period
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};
