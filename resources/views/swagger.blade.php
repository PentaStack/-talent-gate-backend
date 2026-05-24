<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Talent-Gate API Documentation</title>
    <!-- Swagger UI CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui.css" />
    <style>
      html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
      *, *:before, *:after { box-sizing: inherit; }
      body { margin: 0; background: #fafafa; }
      .swagger-ui .topbar { background-color: #1a1a1c; } /* Talent-Gate brand color */
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <!-- Swagger UI JS bundles -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui-bundle.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/4.18.3/swagger-ui-standalone-preset.js"></script>
    
    <script>
    window.onload = function() {
      const ui = SwaggerUIBundle({
        url: "/swagger.yaml",
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
          SwaggerUIBundle.presets.apis,
          SwaggerUIStandalonePreset
        ],
        plugins: [
          SwaggerUIBundle.plugins.DownloadUrl
        ],
        layout: "StandaloneLayout",
        requestInterceptor: function (request) {
          request.headers['Accept'] = 'application/json';
          request.headers['X-Requested-With'] = 'XMLHttpRequest';
          // If you need to attach CSRF token manually from cookie:
          const match = document.cookie.match(new RegExp('(^| )XSRF-TOKEN=([^;]+)'));
          if (match) request.headers['X-XSRF-TOKEN'] = decodeURIComponent(match[2]);
          return request;
        }
      });
      window.ui = ui;
    };
  </script>
</body>
</html>
