import React from 'react';
import { Head } from '@inertiajs/react';

interface MaintenanceProps {
  client: string;
  event: {
    name: string;
    slug: string;
  };
  maintenance: {
    title: string;
    message: string;
    expected_finish: string | null;
  };
  props: {
    logo?: string;
    logo_alt?: string;
    primary_color?: string;
    secondary_color?: string;
    text_primary_color?: string;
    text_secondary_color?: string;
  };
}

export default function Maintenance({ client, event, maintenance, props }: MaintenanceProps): React.ReactElement {
  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <Head title={maintenance.title} />
      
      <div className="max-w-md w-full space-y-8 bg-white p-8 rounded-lg shadow-md"
           style={{
             backgroundColor: props.secondary_color || '#ffffff',
             color: props.text_primary_color || '#000000'
           }}>
        {props.logo && (
          <div className="flex justify-center">
            <img 
              src={props.logo} 
              alt={props.logo_alt || 'Logo'} 
              className="h-16 w-auto"
            />
          </div>
        )}
        
        <div className="text-center">
          <h2 className="mt-6 text-3xl font-extrabold" 
              style={{ color: props.text_primary_color || '#1f2937' }}>
            {maintenance.title}
          </h2>
          <p className="mt-2 text-sm" 
             style={{ color: props.text_secondary_color || '#4b5563' }}>
            {maintenance.message}
          </p>
          
          {maintenance.expected_finish && (
            <div className="mt-4 rounded-md p-4"
                 style={{ 
                   backgroundColor: `${props.primary_color || '#fef3c7'}20`,
                   borderColor: props.primary_color || '#fcd34d'
                 }}>
              <div className="flex">
                <div className="flex-shrink-0">
                  <svg className="h-5 w-5" 
                       style={{ color: props.primary_color || '#f59e0b' }}
                       xmlns="http://www.w3.org/2000/svg" 
                       viewBox="0 0 20 20" 
                       fill="currentColor">
                    <path fillRule="evenodd" 
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" 
                          clipRule="evenodd" />
                  </svg>
                </div>
                <div className="ml-3">
                  <h3 className="text-sm font-medium"
                      style={{ color: props.text_primary_color || '#92400e' }}>
                    Expected completion
                  </h3>
                  <div className="mt-2 text-sm"
                       style={{ color: props.text_secondary_color || '#b45309' }}>
                    <p>{maintenance.expected_finish}</p>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
        
        <div className="mt-6 text-center text-sm"
             style={{ color: props.text_secondary_color || '#6b7280' }}>
          <p>Please check back later. We apologize for the inconvenience.</p>
        </div>
      </div>
    </div>
  );
}