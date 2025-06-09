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
        maxLength = 255, // Default maxLength to 255 if not provided
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

    const handleInput = (e: React.ChangeEvent<HTMLInputElement>) => {
        const inputValue = e.target.value;

        if (type === 'number') {
            // Remove any non-numeric characters (except for decimal point if needed)
            const filteredValue = inputValue.replace(/[^0-9.]/g, '');

            // Update the input value with the filtered value
            e.target.value = filteredValue;

            // Ignore if exceeds maxLength
            if (filteredValue.length > maxLength) {
                e.target.value = filteredValue.slice(0, maxLength);
            }
        }
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
