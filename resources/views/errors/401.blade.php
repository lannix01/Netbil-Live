@include('errors.layout', [
    'statusCode' => 401,
    'title' => 'Authentication required.',
    'message' => 'Please sign in to continue with this action.',
])
