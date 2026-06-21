#!/data/data/com.termux/files/usr/bin/bash
#============================================
# XVIDSUP Termux Uploader
# Upload video dari HP ke PC server
# 
# Cara pakai:
#   1. chmod +x termux-uploader.sh
#   2. ./termux-uploader.sh
#============================================

# Konfigurasi — ganti sesuai IP PC-mu
PC_IP="192.168.42.246"
PC_PORT="8080"
SERVER_URL="http://$PC_IP:$PC_PORT"

# Folder sumber video (default: Download)
SRC_DIR="$HOME/storage/downloads"

# Folder backup setelah upload (optional)
DONE_DIR="$HOME/storage/downloads/uploaded"

# Format video yang didukung
VIDEO_EXTS=("mp4" "avi" "mkv" "mov" "wmv" "flv" "webm" "m4v" "3gp" "mpeg")

# Warna
RED='\033[1;31m'
GREEN='\033[1;32m'
YELLOW='\033[1;33m'
BLUE='\033[1;34m'
NC='\033[0m'

#==================== FUNCTIONS ====================

banner() {
    clear
    echo -e "${BLUE}"
    echo "  ╔══════════════════════════════════════╗"
    echo "  ║        XVIDSUP Termux Uploader       ║"
    echo "  ║    Upload video → LuluStream + DB    ║"
    echo "  ╚══════════════════════════════════════╝"
    echo -e "${NC}"
}

check_storage() {
    if [ ! -d "$HOME/storage" ]; then
        echo -e "${YELLOW}[!] Izinkan akses storage dulu:${NC}"
        termux-setup-storage
        echo -e "${YELLOW}  Setelah izin diberikan, jalanin ulang script.${NC}"
        exit 1
    fi
}

check_server() {
    echo -e "${BLUE}[*] Cek koneksi ke server...${NC}"
    if curl -s --connect-timeout 3 "$SERVER_URL/api/server-info" > /dev/null 2>&1; then
        echo -e "${GREEN}[✓] Server online: $SERVER_URL${NC}"
        return 0
    else
        echo -e "${RED}[✗] Server $SERVER_URL tidak reachable!${NC}"
        echo -e "${YELLOW}  Pastikan:${NC}"
        echo "  1. PC sudah jalanin start.bat"
        echo "  2. HP terhubung via USB tether / WiFi yang sama"
        echo "  3. IP PC benar (cek di output start.bat)"
        echo ""
        read -p "  Masukkan IP PC manual: " custom_ip
        if [ -n "$custom_ip" ]; then
            PC_IP="$custom_ip"
            SERVER_URL="http://$PC_IP:$PC_PORT"
            if curl -s --connect-timeout 3 "$SERVER_URL/api/server-info" > /dev/null 2>&1; then
                echo -e "${GREEN}[✓] Server online: $SERVER_URL${NC}"
                return 0
            fi
        fi
        return 1
    fi
}

scan_videos() {
    local dir="$1"
    local files=()
    
    if [ ! -d "$dir" ]; then
        echo -e "${RED}[!] Folder $dir tidak ditemukan${NC}"
        return 1
    fi
    
    for ext in "${VIDEO_EXTS[@]}"; do
        while IFS= read -r -d '' f; do
            files+=("$f")
        done < <(find "$dir" -maxdepth 2 -iname "*.$ext" -type f -print0 2>/dev/null)
    done
    
    echo "${files[@]}"
}

upload_file() {
    local filepath="$1"
    local filename
    filename=$(basename "$filepath")
    local filesize
    filesize=$(du -h "$filepath" | cut -f1)
    
    echo -e "${YELLOW}[→] Upload: $filename ($filesize)${NC}"
    
    # Upload via multipart POST
    response=$(curl -s -w "\n%{http_code}" -X POST \
        -F "video=@$filepath" \
        -F "category=" \
        "$SERVER_URL/api/upload-phone" 2>&1)
    
    http_code=$(echo "$response" | tail -1)
    body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" = "200" ]; then
        if echo "$body" | grep -q '"success":true'; then
            file_code=$(echo "$body" | grep -o '"file_code":"[^"]*"' | cut -d'"' -f4)
            echo -e "${GREEN}[✓] BERHASIL → File Code: $file_code${NC}"
            # Pindahin file ke folder done
            if [ -d "$DONE_DIR" ]; then
                mv "$filepath" "$DONE_DIR/"
            fi
            return 0
        elif echo "$body" | grep -q '"skipped":true'; then
            echo -e "${YELLOW}[⏭] SKIP — sudah ada di DB${NC}"
            # Tetap pindahkan
            if [ -d "$DONE_DIR" ]; then
                mv "$filepath" "$DONE_DIR/"
            fi
            return 2
        else
            error_msg=$(echo "$body" | grep -o '"error":"[^"]*"' | cut -d'"' -f4)
            echo -e "${RED}[✗] GAGAL: ${error_msg:-Unknown error}${NC}"
            return 1
        fi
    else
        echo -e "${RED}[✗] HTTP Error: $http_code${NC}"
        echo "$body"
        return 1
    fi
}

#==================== MAIN ====================

banner
check_storage

# Setup folder done
if [ ! -d "$DONE_DIR" ]; then
    mkdir -p "$DONE_DIR"
fi

# Cek koneksi
if ! check_server; then
    echo ""
    echo -e "${RED}Server tidak bisa dijangkau.${NC}"
    echo -e "${YELLOW}Cara fix:${NC}"
    echo "  1. Di PC, jalanin start.bat"
    echo "  2. Catet IP yang muncul"
    echo "  3. Edit baris PC_IP di script ini"
    read -p "  [Enter] untuk keluar"
    exit 1
fi

echo ""
echo -e "${BLUE}╔══════════════════════════════════════╗${NC}"
echo -e "${BLUE}║           PILIHAN MENU               ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════╝${NC}"
echo "  1. Upload semua video dari Download/"
echo "  2. Pilih file tertentu"
echo "  3. Watch mode (pantau folder otomatis)"
echo "  4. Cek status server"
echo "  q. Keluar"
echo ""
read -p "Pilih [1-4/q]: " menu

case "$menu" in
    1)
        echo ""
        echo -e "${BLUE}[*] Scan folder $SRC_DIR...${NC}"
        
        mapfile -t video_files < <(scan_videos "$SRC_DIR")
        
        if [ ${#video_files[@]} -eq 0 ]; then
            echo -e "${YELLOW}[!] Tidak ada file video di $SRC_DIR${NC}"
            read -p "[Enter] untuk keluar"
            exit 0
        fi
        
        echo -e "${GREEN}[✓] Ditemukan ${#video_files[@]} file video${NC}"
        echo ""
        
        success=0
        failed=0
        skipped=0
        total=${#video_files[@]}
        current=0
        
        for file in "${video_files[@]}"; do
            current=$((current + 1))
            echo -e "${BLUE}[$current/$total]${NC}"
            upload_file "$file"
            result=$?
            case $result in
                0) success=$((success + 1)) ;;
                1) failed=$((failed + 1)) ;;
                2) skipped=$((skipped + 1)) ;;
            esac
            echo ""
        done
        
        echo "═══════════════════════════════════════"
        echo -e "  ${GREEN}Berhasil: $success${NC}"
        echo -e "  ${YELLOW}Skip:     $skipped${NC}"
        echo -e "  ${RED}Gagal:    $failed${NC}"
        echo "═══════════════════════════════════════"
        ;;
        
    2)
        echo ""
        mapfile -t video_files < <(scan_videos "$SRC_DIR")

        if [ ${#video_files[@]} -eq 0 ]; then
            echo -e "${YELLOW}[!] Tidak ada file video${NC}"
            read -p "[Enter] untuk keluar"
            exit 0
        fi
        
        echo -e "${BLUE}Pilih file:${NC}"
        for i in "${!video_files[@]}"; do
            fname=$(basename "${video_files[$i]}")
            fsize=$(du -h "${video_files[$i]}" | cut -f1)
            echo "  $((i+1)). $fname ($fsize)"
        done
        echo ""
        read -p "Nomor file (contoh: 1 3 5 atau 1-5): " selection
        
        # Parse selection
        selected=()
        if echo "$selection" | grep -q '-'; then
            start=$(echo "$selection" | cut -d'-' -f1)
            end=$(echo "$selection" | cut -d'-' -f2)
            for ((i=start-1; i<end && i<${#video_files[@]}; i++)); do
                selected+=("${video_files[$i]}")
            done
        else
            for num in $selection; do
                idx=$((num-1))
                if [ "$idx" -ge 0 ] && [ "$idx" -lt "${#video_files[@]}" ]; then
                    selected+=("${video_files[$idx]}")
                fi
            done
        fi
        
        echo ""
        for file in "${selected[@]}"; do
            upload_file "$file"
            echo ""
        done
        ;;
        
    3)
        echo ""
        echo -e "${BLUE}[*] Watch mode aktif${NC}"
        echo -e "${YELLOW}  Script akan pantau folder $SRC_DIR${NC}"
        echo -e "${YELLOW}  Setiap ada video baru → auto upload${NC}"
        echo -e "${YELLOW}  Tekan CTRL+C untuk stop${NC}"
        echo ""
        
        # Simple watch loop
        declare -A seen_files
        # Seed with existing files
        while IFS= read -r -d '' f; do
            seen_files["$f"]=1
        done < <(find "$SRC_DIR" -maxdepth 2 -type f \( -iname "*.mp4" -o -iname "*.mkv" -o -iname "*.avi" \) -print0 2>/dev/null)
        
        echo -e "${GREEN}[✓] Memantau...${NC}"
        
        while true; do
            while IFS= read -r -d '' f; do
                if [ -z "${seen_files[$f]:-}" ]; then
                    echo ""
                    echo -e "${GREEN}[!] File baru terdeteksi: $(basename "$f")${NC}"
                    seen_files["$f"]=1
                    upload_file "$f"
                fi
            done < <(find "$SRC_DIR" -maxdepth 2 -type f \( -iname "*.mp4" -o -iname "*.mkv" -o -iname "*.avi" \) -newer "$SRC_DIR" -print0 2>/dev/null)
            
            sleep 5
        done
        ;;
        
    4)
        echo ""
        echo -e "${BLUE}[*] Server info:${NC}"
        curl -s "$SERVER_URL/api/server-info" | python3 -m json.tool 2>/dev/null || curl -s "$SERVER_URL/api/server-info"
        echo ""
        read -p "[Enter] untuk keluar"
        ;;
        
    q|Q)
        echo "Bye!"
        exit 0
        ;;
esac

echo ""
read -p "Selesai! [Enter] untuk menutup"
