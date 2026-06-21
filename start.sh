#!/bin/bash
# Render start script — public only (no admin/uploader)
php -d upload_max_filesize=2G -d post_max_size=2G -S 0.0.0.0:$PORT router_public.php
