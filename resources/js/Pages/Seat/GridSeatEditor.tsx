import React, { useState, useEffect } from 'react';
import { Layout, SeatItem, Category, SeatStatus, LayoutItem } from './types';
import { router } from '@inertiajs/react';
import { AlertDialog, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogAction } from "@/Components/ui/alert-dialog";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/Components/ui/dialog";
import { Button } from "@/Components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";

interface Props {
    initialLayout?: Layout;
    onSave?: (layout: Layout) => void;
    venueId: string;
  }
  
  interface GridCell {
    type: 'empty' | 'seat' | 'label';
    item?: SeatItem;
  }
  
  // Helper function to convert number to Excel-style column label
  const getRowLabel = (num: number): string => {
    let dividend = num;
    let columnName = '';
    let modulo;
  
    while (dividend > 0) {
      modulo = (dividend - 1) % 26;
      columnName = String.fromCharCode(65 + modulo) + columnName;
      dividend = Math.floor((dividend - 1) / 26);
    }
  
    return columnName;
  };
  
  // Helper function to convert Excel-style column label to number
  const getRowNumber = (label: string): number => {
    let result = 0;
    for (let i = 0; i < label.length; i++) {
      result *= 26;
      result += label.charCodeAt(i) - 64; // 'A' is 65 in ASCII
    }
    return result;
  };
  
  const GridSeatEditor: React.FC<Props> = ({ initialLayout, onSave, venueId }) => {
    const [rows, setRows] = useState(10);
    const [columns, setColumns] = useState(15);
    const [grid, setGrid] = useState<GridCell[][]>([]);
    const [selectedCell, setSelectedCell] = useState<{row: number, col: number} | null>(null);
    const [showSeatDialog, setShowSeatDialog] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [editMode, setEditMode] = useState<'add' | 'edit' | null>(null);
    const [selectedCategory, setSelectedCategory] = useState<Category>('silver');
    const [selectedStatus, setSelectedStatus] = useState<SeatStatus>('available');
  
    useEffect(() => {
      initializeGrid();
    }, [rows, columns]);
  
    // Function to find the highest occupied row
  const findHighestRow = (items: LayoutItem[]): number => {
    let maxRow = 0;
    items.forEach(item => {
      if (item.type === 'seat') {
        const rowNum = typeof item.row === 'string' 
          ? getRowNumber(item.row) - 1 
          : item.row;
        maxRow = Math.max(maxRow, rowNum);
      }
    });
    return maxRow;
  };

  // Function to find the highest occupied column
  const findHighestColumn = (items: LayoutItem[]): number => {
    let maxCol = 0;
    items.forEach(item => {
      if (item.type === 'seat') {
        maxCol = Math.max(maxCol, item.column);
      }
    });
    return maxCol;
  };

  // Update initializeGrid to automatically set dimensions based on seats
  useEffect(() => {
    if (initialLayout?.items?.length) {
      const maxRow = findHighestRow(initialLayout.items);
      const maxCol = findHighestColumn(initialLayout.items);
      setRows(Math.max(maxRow + 1, rows));
      setColumns(Math.max(maxCol, columns));
    }
  }, [initialLayout]);

  const initializeGrid = () => {
    const newGrid: GridCell[][] = Array(rows).fill(null).map(() =>
      Array(columns).fill(null).map(() => ({ type: 'empty' }))
    );

    if (initialLayout) {
      initialLayout.items.forEach(item => {
        if (item.type === 'seat') {
          const seatItem = item as SeatItem;
          const rowIndex = typeof seatItem.row === 'string' 
            ? getRowNumber(seatItem.row) - 1
            : seatItem.row;
          const colIndex = seatItem.column - 1;
          
          if (rowIndex >= 0 && rowIndex < rows && colIndex >= 0 && colIndex < columns) {
            newGrid[rowIndex][colIndex] = {
              type: 'seat',
              item: seatItem
            };
          }
        }
      });
    }

    setGrid(newGrid);
  };
  
    const handleCellClick = (rowIndex: number, colIndex: number) => {
      setSelectedCell({ row: rowIndex, col: colIndex });
      const cell = grid[rowIndex][colIndex];
      
      if (cell.type === 'empty') {
        setEditMode('add');
        setShowSeatDialog(true);
        setSelectedStatus('available');
        setSelectedCategory('silver');
      } else if (cell.type === 'seat' && cell.item) {
        setEditMode('edit');
        setShowSeatDialog(true);
        setSelectedCategory(cell.item.category);
        setSelectedStatus(cell.item.status);
      }
    };
  
    const handleAddSeat = () => {
      if (!selectedCell) return;
      
      const newGrid = [...grid];
      const rowLabel = getRowLabel(selectedCell.row + 1);
      
      const newSeat: SeatItem = {
        type: 'seat',
        seat_id: `${rowLabel}${selectedCell.col + 1}`,
        seat_number: `${rowLabel}${selectedCell.col + 1}`,
        row: rowLabel,
        column: selectedCell.col + 1,
        status: selectedStatus,
        category: selectedCategory
      };
  
      newGrid[selectedCell.row][selectedCell.col] = {
        type: 'seat',
        item: newSeat
      };
  
      setGrid(newGrid);
      setShowSeatDialog(false);
      reorderSeatNumbers();
    };
  
    const handleDeleteSeat = () => {
      if (!selectedCell) return;
      
      const newGrid = [...grid];
      newGrid[selectedCell.row][selectedCell.col] = { type: 'empty' };
      
      setGrid(newGrid);
      setShowDeleteDialog(false);
      reorderSeatNumbers();
    };
  
    const reorderSeatNumbers = () => {
      const newGrid = [...grid];
      const seatCounters: { [key: string]: number } = {};
  
      for (let i = 0; i < rows; i++) {
        const rowLabel = getRowLabel(i + 1);
        seatCounters[rowLabel] = 1;
  
        for (let j = 0; j < columns; j++) {
          const cell = newGrid[i][j];
          if (cell.type === 'seat' && cell.item) {
            cell.item.seat_number = `${rowLabel}${seatCounters[rowLabel]}`;
            seatCounters[rowLabel]++;
          }
        }
      }
  
      setGrid(newGrid);
    };
  
    const handleSave = () => {
      const items: SeatItem[] = [];
      
      grid.forEach((row, rowIndex) => {
        row.forEach((cell, colIndex) => {
          if (cell.type === 'seat' && cell.item) {
            items.push(cell.item);
          }
        });
      });
  
      const layout: Layout = {
        totalRows: rows,
        totalColumns: columns,
        items: items
      };
  
      onSave?.(layout);
    };
  
    const getCellColor = (cell: GridCell): string => {
      if (cell.type === 'empty') return 'bg-gray-100';
      if (cell.type === 'label') return 'bg-gray-200';
      
      const seat = cell.item;
      if (!seat) return 'bg-gray-100';
  
      if (seat.status !== 'available') {
        switch (seat.status) {
          case 'booked': return 'bg-red-500';
          case 'in_transaction': return 'bg-yellow-500';
          case 'not_available': return 'bg-gray-400';
        }
      }
      
      switch (seat.category) {
        case 'diamond': return 'bg-cyan-400';
        case 'gold': return 'bg-yellow-400';
        case 'silver': return 'bg-gray-300';
        default: return 'bg-gray-200';
      }
    };
  
    return (
      <div className="p-6">
        <div className="mb-6 flex gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700" htmlFor="rows">Rows</label>
            <input
              id="rows"
              type="number"
              min="1"
              value={rows}
              onChange={(e) => {
                const value = parseInt(e.target.value);
                if (value > 0) {
                  setRows(value);
                }
              }}
              className="mt-1 block w-20 rounded-md border-gray-300 shadow-sm"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700" htmlFor="columns">Columns</label>
            <input
              id="columns"
              type="number"
              min="1"
              value={columns}
              onChange={(e) => {
                const value = parseInt(e.target.value);
                if (value > 0) {
                  setColumns(value);
                }
              }}
              className="mt-1 block w-20 rounded-md border-gray-300 shadow-sm"
            />
          </div>
        </div>
  
        <div className="mb-6">
          <div className="grid gap-1">
            {[...grid].reverse().map((row, reversedIndex) => {
              const originalIndex = grid.length - 1 - reversedIndex;
              const rowLabel = getRowLabel(originalIndex + 1);
              return (
                <div key={reversedIndex} className="flex gap-1">
                  {row.map((cell, colIndex) => (
                    <div
                      key={colIndex}
                      onClick={() => handleCellClick(originalIndex, colIndex)}
                      className={`
                        w-8 h-8 
                        flex items-center justify-center 
                        border rounded 
                        ${getCellColor(cell)}
                        cursor-pointer 
                        hover:opacity-80
                        text-xs
                      `}
                    >
                      {cell.type === 'seat' && cell.item?.seat_number}
                    </div>
                  ))}
                </div>
              );
            })}
          </div>
  
          <div className="mt-4 w-60 h-8 bg-white border border-gray-200 flex items-center justify-center rounded text-sm">
            Panggung
          </div>
        </div>
  
        <Button onClick={handleSave} className="mt-4">
          Save Layout
        </Button>
    
          {/* Add/Edit Seat Dialog */}
          <Dialog open={showSeatDialog} onOpenChange={setShowSeatDialog}>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>{editMode === 'add' ? 'Add New Seat' : 'Edit Seat'}</DialogTitle>
              </DialogHeader>
              <div className="space-y-4 py-4">
                <div>
                  <label className="block text-sm font-medium mb-2">Category</label>
                  <Select value={selectedCategory} onValueChange={(value: Category) => setSelectedCategory(value)}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="diamond">Diamond</SelectItem>
                      <SelectItem value="gold">Gold</SelectItem>
                      <SelectItem value="silver">Silver</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
    
                <div>
                  <label className="block text-sm font-medium mb-2">Status</label>
                  <Select value={selectedStatus} onValueChange={(value: SeatStatus) => setSelectedStatus(value)}>
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="available">Available</SelectItem>
                      <SelectItem value="booked">Booked</SelectItem>
                      <SelectItem value="in_transaction">In Transaction</SelectItem>
                      <SelectItem value="not_available">Not Available</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setShowSeatDialog(false)}>
                  Cancel
                </Button>
                <Button onClick={handleAddSeat}>
                  {editMode === 'add' ? 'Add Seat' : 'Update Seat'}
                </Button>
                {editMode === 'edit' && (
                  <Button variant="destructive" onClick={() => {
                    setShowSeatDialog(false);
                    setShowDeleteDialog(true);
                  }}>
                    Delete Seat
                  </Button>
                )}
              </DialogFooter>
            </DialogContent>
          </Dialog>
    
          {/* Delete Confirmation Dialog */}
          <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
            <AlertDialogContent>
              <AlertDialogHeader>
                <AlertDialogTitle>Delete Seat</AlertDialogTitle>
                <AlertDialogDescription>
                  Are you sure you want to delete this seat? This action cannot be undone.
                </AlertDialogDescription>
              </AlertDialogHeader>
              <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction onClick={handleDeleteSeat}>Delete</AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>
        </div>
      );
    };
    
    export default GridSeatEditor;