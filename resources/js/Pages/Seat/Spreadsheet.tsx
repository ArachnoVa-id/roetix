import React from 'react';
import { Head } from '@inertiajs/react';
import SeatSpreadsheetEditor from './SeatSpreadsheetEditor';
import { Layout } from './types';

interface Props {
  layout: Layout;
}

const SpreadsheetPage: React.FC<Props> = ({ layout }) => {
  return (
    <>
      <Head title="Seat Spreadsheet" />
      <div className="py-12">
        <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div className="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <SeatSpreadsheetEditor layout={layout} />
          </div>
        </div>
      </div>
    </>
  );
};

export default SpreadsheetPage;