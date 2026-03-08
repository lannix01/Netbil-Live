@include('errors.layout', [
    'statusCode' => 403,
    'title' => 'Access denied.',
    'message' => 'You do not have permission to view this resource.',
])
