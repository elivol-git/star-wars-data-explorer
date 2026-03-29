public function search(Request $request, StarWarsAiSearchService $service)
{
    $q = $request->input('q');

    if (!$q) {
        return response()->json([
            'error' => 'Missing query'
        ], 400);
    }

    try {
        $result = $service->search($q);
        return response()->json($result);

    } catch (\Throwable $e) {

        // 🚨 DO NOT use Log here (it crashes)
        return response()->json([
            'error' => 'AI search failed',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
}