export interface GridDimensions {
    top: number;
    bottom: number;
    left: number;
    right: number;
}

export interface LayoutItem {
    row: string | number;
    column: number;
    // [key: string]: any;
}

// Helper function to convert Excel-style column label to number
export const getRowNumber = (label: string): number => {
    let result = 0;

    // Iterate through each character in the label
    for (let i = 0; i < label.length; i++) {
        // For each position, multiply the result so far by 26
        result *= 26;
        // Add the value of the current character (A=1, B=2, ..., Z=26)
        const charValue = label.charCodeAt(i) - 64; // 'A' is 65 in ASCII
        result += charValue;
    }

    // Return 0-based index
    return result - 1;
};

// Function to get row label from bottom-up position
export const getAdjustedRowLabel = (index: number): string => {
    // Convert to 1-based index for Excel-style labels
    const rowNumber = index + 1;

    if (rowNumber <= 0) return '';

    // Convert number to Excel-style column label (A, B, C, ... Z, AA, AB, etc.)
    let label = '';
    let n = rowNumber;

    while (n > 0) {
        // Get the remainder when divided by 26 (number of letters)
        let remainder = n % 26;

        // If remainder is 0, use 'Z' and adjust n
        if (remainder === 0) {
            remainder = 26;
            n -= 1;
        }

        // Convert number to letter (A=1, B=2, ...) and add to front of label
        label = String.fromCharCode(64 + remainder) + label;

        // Integer division by 26 to get the next digit
        n = Math.floor(n / 26);
    }

    return label;
};

// Function to find highest row and adjust dimensions
export const findHighestRow = (items: LayoutItem[]): number => {
    let maxRow = 0;
    items.forEach((item) => {
        const rowNum =
            typeof item.row === 'string' ? getRowNumber(item.row) : item.row;
        maxRow = Math.max(maxRow, rowNum);
    });
    return maxRow;
};

// Function to find highest column and adjust dimensions
export const findHighestColumn = (items: LayoutItem[]): number => {
    let maxCol = 0;
    items.forEach((item) => {
        maxCol = Math.max(maxCol, item.column);
    });
    return maxCol;
};
