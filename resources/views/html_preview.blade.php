<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ZeusMailer - Live HTML Preview</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #5a67d8;
            --secondary: #805ad5;
        }

        body {
            background: linear-gradient(135deg, #eef2f3, #dfe9f3);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        /* HEADER (Reduced Height) */
        .header {
            background: linear-gradient(120deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem 0; /* reduced */
            margin-bottom: 1.8rem; /* reduced */
            border-bottom-left-radius: 1.5rem;
            border-bottom-right-radius: 1.5rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .card {
            border: none;
            border-radius: 0.8rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        #html_input {
            font-family: monospace;
            min-height: 70vh; /* taller input area */
            resize: none;
        }

        #preview {
            background: #fff;
            border-radius: 0.8rem;
            height: 70vh; /* same as editor height */
            overflow: hidden;
            box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.05);
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 0.8rem;
            background: #fff;
        }

        .form-label {
            font-weight: 600;
        }

        @media (max-width: 992px) {
            #html_input,
            #preview {
                min-height: 50vh;
                height: 50vh;
            }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <div class="container d-flex justify-content-between align-items-center">
            <h1 class="fs-4 m-0"><i class="fas fa-code me-2"></i> ZeusMailer - HTML Preview</h1>
            <a href="{{ route('campaign.index') }}" class="btn btn-light fw-semibold">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="container pb-5">
        <div class="card">
            <div class="card-header bg-primary bg-gradient text-white fw-semibold">
                <i class="fas fa-eye me-2"></i> Live HTML Preview Tool
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <!-- HTML INPUT -->
                    <div class="col-lg-6 col-md-12">
                        <label class="form-label mb-2">
                            <i class="fas fa-file-code me-1"></i> HTML Content
                        </label>
                        <textarea id="html_input" class="form-control"
                            placeholder="<h1>Hello ZeusMailer</h1><p>This is a live preview test."></textarea>
                    </div>

                    <!-- PREVIEW AREA -->
                    <div class="col-lg-6 col-md-12">
                        <label class="form-label mb-2">
                            <i class="fas fa-eye me-1"></i> Live Preview
                        </label>
                        <div id="preview">
                            <iframe id="previewFrame"></iframe>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button id="clearBtn" class="btn btn-outline-danger">
                        <i class="fas fa-trash-alt me-1"></i> Clear
                    </button>
                    <button id="copyBtn" class="btn btn-outline-primary ms-2">
                        <i class="fas fa-copy me-1"></i> Copy HTML
                    </button>
                    <button id="downloadBtn" class="btn btn-success ms-2">
                        <i class="fas fa-download me-1"></i> Download HTML File
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const htmlInput = document.getElementById('html_input');
        const previewFrame = document.getElementById('previewFrame');
        const clearBtn = document.getElementById('clearBtn');
        const copyBtn = document.getElementById('copyBtn');
        const downloadBtn = document.getElementById('downloadBtn');

        // Update preview in real-time
        htmlInput.addEventListener('input', updatePreview);

        function updatePreview() {
            const content = htmlInput.value.trim() || '<p style="text-align:center; color:gray;">Start typing HTML...</p>';
            const doc = previewFrame.contentDocument || previewFrame.contentWindow.document;
            doc.open();
            doc.write(content);
            doc.close();
        }

        // Clear button
        clearBtn.addEventListener('click', () => {
            htmlInput.value = '';
            updatePreview();
        });

        // Copy HTML
        copyBtn.addEventListener('click', async () => {
            await navigator.clipboard.writeText(htmlInput.value);
            copyBtn.innerHTML = '<i class="fas fa-check me-1"></i> Copied!';
            setTimeout(() => copyBtn.innerHTML = '<i class="fas fa-copy me-1"></i> Copy HTML', 1500);
        });

        // Download HTML
        downloadBtn.addEventListener('click', () => {
            const blob = new Blob([htmlInput.value], { type: 'text/html' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'zeusmailer_preview.html';
            link.click();
        });

        // Initialize preview
        updatePreview();
    </script>
</body>
</html>
