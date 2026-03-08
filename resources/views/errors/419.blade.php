@include('errors.layout', [
    'statusCode' => 419,
    'title' => 'Session expired.',
    'message' => 'Your session has expired. Refresh the page and try again.',
])
