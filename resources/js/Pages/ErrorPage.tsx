import React from 'react';

interface ErrorProps {
    code: number;
    message: string;
    headers: Record<string, string>;
}

const getMessage = (code: number): string => {
    switch (code) {
        case 400:
            return 'Bad Request - The server could not understand the request.';
        case 401:
            return 'Unauthorized - You need to log in to access this resource.';
        case 402:
            return 'Payment Required - Access to this resource requires payment.';
        case 403:
            return 'Forbidden - You do not have permission to access this page.';
        case 404:
            return 'Page Not Found - The resource you are looking for does not exist.';
        case 405:
            return 'Method Not Allowed - The requested method is not supported.';
        case 406:
            return 'Not Acceptable - The server cannot produce a response matching your request.';
        case 407:
            return 'Proxy Authentication Required - Authentication with a proxy is required.';
        case 408:
            return 'Request Timeout - The server took too long to respond.';
        case 409:
            return 'Conflict - The request could not be completed due to a conflict.';
        case 410:
            return 'Gone - The requested resource is no longer available.';
        case 411:
            return 'Length Required - A valid Content-Length header is required.';
        case 412:
            return 'Precondition Failed - The request preconditions were not met.';
        case 413:
            return 'Payload Too Large - The request body is too large.';
        case 414:
            return 'URI Too Long - The requested URI is too long.';
        case 415:
            return 'Unsupported Media Type - The server does not support the media type.';
        case 416:
            return 'Range Not Satisfiable - The requested range cannot be fulfilled.';
        case 417:
            return 'Expectation Failed - The server could not meet the expectations set.';
        case 418:
            return "I'm a Teapot ☕ - A fun Easter egg from RFC 2324.";
        case 419:
            return 'Authentication Timeout - The session has expired.';
        case 420:
            return 'Method Failure - The method was not executed.';
        case 421:
            return 'Misdirected Request - The request was directed to the wrong server.';
        case 422:
            return 'Unprocessable Entity - The request contains semantic errors.';
        case 423:
            return 'Locked - The resource is currently locked.';
        case 424:
            return 'Failed Dependency - A dependency for this request failed.';
        case 425:
            return 'Too Early - The server is unwilling to process the request at this time.';
        case 426:
            return 'Upgrade Required - The client must switch to a different protocol.';
        case 428:
            return 'Precondition Required - A precondition header is required.';
        case 429:
            return 'Too Many Requests - You have exceeded the allowed number of requests.';
        case 431:
            return 'Request Header Fields Too Large - Headers exceeded the allowed size.';
        case 451:
            return 'Unavailable for Legal Reasons - Access restricted due to legal requirements.';
        case 500:
            return 'Internal Server Error - Something went wrong on our end.';
        case 501:
            return 'Not Implemented - The server does not support the requested feature.';
        case 502:
            return 'Bad Gateway - Received an invalid response from an upstream server.';
        case 503:
            return 'Service Unavailable - The server is temporarily unable to handle the request.';
        case 504:
            return 'Gateway Timeout - The server did not receive a response in time.';
        case 505:
            return 'HTTP Version Not Supported - The HTTP version is not supported.';
        case 506:
            return 'Variant Also Negotiates - A transparent content negotiation error.';
        case 507:
            return 'Insufficient Storage - The server has run out of space.';
        case 508:
            return 'Loop Detected - The request caused an infinite loop.';
        case 510:
            return 'Not Extended - Further extensions are required for processing.';
        case 511:
            return 'Network Authentication Required - The client must authenticate to gain network access.';
        default:
            return 'An error occurred - Something unexpected happened.';
    }
};

const ErrorPage: React.FC<ErrorProps> = ({ code, message, headers }) => {
    return (
        <div className="flex h-screen items-center justify-center gap-5">
            <img
                src="images/novatix-logo.jpeg"
                alt="NovaTix Logo"
                className="h-48 w-48 rounded-xl"
            />
            <div className="flex flex-col gap-3">
                <h3 className="text-xl font-bold">
                    NovaTix:{' '}
                    <span className="text-lg font-normal">
                        Ticketing Solutions
                    </span>
                </h3>

                <div className="-mt-2 flex flex-col">
                    <h1 className="text-6xl font-bold">Error {code}</h1>
                    <p className="text-xl">{message || getMessage(code)}</p>
                </div>

                <a
                    href="/"
                    className={
                        'w-fit rounded bg-blue-600 px-4 py-2 text-white ' +
                        (headers['isRedirecting'] === 'false' ? 'hidden' : '')
                    }
                >
                    Go Home
                </a>
            </div>
        </div>
    );
};

export default ErrorPage;
