import React, { FormEvent } from 'react';
import { Head, useForm } from '@inertiajs/react';

interface LockedEventProps {
  client: string;
  event: {
    name: string;
    slug: string;
  };
  hasAttemptedLogin: boolean;
  loginError: string | null;
  props: {
    logo?: string;
    logo_alt?: string;
    primary_color?: string;
    secondary_color?: string;
    text_primary_color?: string;
    text_secondary_color?: string;
  };
}

// Define a properly typed interface for the form
interface EventPasswordForm {
    event_password: string;
    [key: string]: string | File | File[]; // Add index signature for generic string keys
  }

export default function LockedEvent({ 
  client, 
  event, 
  hasAttemptedLogin, 
  loginError, 
  props 
}: LockedEventProps): React.ReactElement {
  // Explicitly type the useForm hook with our interface
  const { data, setData, post, processing, errors } = useForm<EventPasswordForm>({
    event_password: '',
  });

  const handleSubmit = (e: FormEvent<HTMLFormElement>): void => {
    e.preventDefault();
    post(route('client.verify-event-password', { client }));
  };

  return (
    <div className="min-h-screen flex flex-col items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <Head title={`${event.name} - Protected Event`} />
      
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
            {event.name}
          </h2>
          <p className="mt-2 text-sm"
             style={{ color: props.text_secondary_color || '#4b5563' }}>
            This event is password protected
          </p>
        </div>
        
        <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
          <div className="rounded-md shadow-sm">
            <div>
              <label htmlFor="event_password" className="sr-only">Password</label>
              <input
                id="event_password"
                name="event_password"
                type="password"
                required
                className="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:z-10 sm:text-sm"
                style={{
                  borderColor: props.text_secondary_color || '#d1d5db',
                  borderRadius: '0.375rem',
                }}
                placeholder="Event password"
                value={data.event_password}
                onChange={(e) => setData('event_password', e.target.value)}
              />
            </div>
          </div>

          {loginError && (
            <div className="text-sm text-red-600">
              {loginError}
            </div>
          )}

          {errors.event_password && (
            <div className="text-sm text-red-600">
              {errors.event_password}
            </div>
          )}

          <div>
            <button
              type="submit"
              disabled={processing}
              className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2"
              style={{
                backgroundColor: props.primary_color || '#4F46E5',
                color: '#ffffff',
                borderRadius: '0.375rem',
              }}
            >
              {processing ? 'Verifying...' : 'Enter Event'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}