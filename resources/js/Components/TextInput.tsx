import {
    forwardRef,
    InputHTMLAttributes,
    useEffect,
    useImperativeHandle,
    useRef,
} from 'react';

export default forwardRef(function TextInput(
    {
        type = 'text',
        className = '',
        isFocused = false,
        ...props
    }: InputHTMLAttributes<HTMLInputElement> & { isFocused?: boolean },
    ref,
) {
    const localRef = useRef<HTMLInputElement>(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    const handleInput = (event: React.ChangeEvent<HTMLInputElement>) => {
        if (type === 'number') {
            // Remove any non-numeric characters
            const sanitizedValue = event.target.value.replace(/[^0-9.-]/g, '');
            event.target.value = sanitizedValue;
        }
        // You can also add a check here to prevent the input if it's not a number
        // if (type === 'number' && isNaN(Number(event.target.value))) {
        //     event.preventDefault(); // Prevents the input
        // }
    };

    return (
        <input
            {...props}
            type={type}
            className={
                'rounded-md border-gray-300 bg-white/10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 ' +
                className
            }
            ref={localRef}
            onInput={handleInput} // Add the onInput event handler
        />
    );
});
