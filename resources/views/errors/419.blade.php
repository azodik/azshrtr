@include('errors.layout', [
    'status' => 419,
    'title' => 'Session expired',
    'message' => 'Your session timed out for security. Refresh the page and try again.',
])
