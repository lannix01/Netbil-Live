@include('errors.layout', [
    'statusCode' => 500,
    'title' => 'Unexpected server error.',
    'message' => 'We hit an internal issue while processing your request. The team can now troubleshoot from logs while you retry.',
])
