@include('errors.layout', [
    'status' => 500,
    'title' => 'Server error',
    'message' => 'Something broke on our side. Try again shortly.',
])
