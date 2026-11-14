<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>API Docs — {{ $namaPkm }}</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui.css">
  <style>body{margin:0;background:#fff}.topbar{display:none}</style>
</head>
<body>
  <div id="swagger"></div>
  <p style="text-align:center;font:12px sans-serif;color:#667">Dibuat di Indonesia · © 2026 Seluruh hak cipta dilindungi · Grup NussCorp Technologies Indonesia</p>
  <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
  <script>
    SwaggerUIBundle({ url: @json(asset('openapi.yaml')), dom_id: '#swagger', deepLinking: true, persistAuthorization: true, tryItOutEnabled: true });
  </script>
</body>
</html>
