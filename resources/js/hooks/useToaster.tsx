import { useState } from 'react';

interface ToasterState {
    message: string;
    type: 'success' | 'error';
    isVisible: boolean;
}

const useToaster = () => {
    const [toasterState, setToasterState] = useState<ToasterState>({
        message: '',
        type: 'success',
        isVisible: false,
    });

    const showToaster = (message: string, type: 'success' | 'error') => {
        setToasterState({
            message,
            type,
            isVisible: true,
        });
    };

    const hideToaster = () => {
        setToasterState((prev) => ({ ...prev, isVisible: false }));
    };

    const showSuccess = (message: string) => showToaster(message, 'success');
    const showError = (message: string) => showToaster(message, 'error');

    return {
        toasterState,
        showSuccess,
        showError,
        hideToaster,
    };
};

export default useToaster;
