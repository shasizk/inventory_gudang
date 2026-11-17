export default function attendanceCamera() {
    return {
        captured: false,
        consented: false,
        preview: null,
        error: '',

        async selectPhoto(event) {
            const file = event.target.files?.[0];
            this.clearPreview();
            try {
                this.error = '';
                this.captured = false;
                if (!file) return;
                if (!file.type.startsWith('image/')) {
                    this.error = 'Pilih file gambar untuk bukti absensi.';
                    this.$refs.photo.value = '';
                    return;
                }

                const image = await createImageBitmap(file);
                const maxSide = 1280;
                const scale = Math.min(1, maxSide / Math.max(image.width, image.height));
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(image.width * scale);
                canvas.height = Math.round(image.height * scale);
                canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
                image.close();

                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.82));
                if (!blob) {
                    throw new Error('Foto gagal diproses.');
                }

                const compressed = new File([blob], `attendance-${Date.now()}.jpg`, { type: 'image/jpeg' });
                const files = new DataTransfer();
                files.items.add(compressed);
                this.$refs.photo.files = files.files;
                this.preview = URL.createObjectURL(blob);
                this.captured = true;
            } catch {
                this.clearPreview();
                this.$refs.photo.value = '';
                this.error = 'Foto tidak dapat dibaca. Coba ambil ulang menggunakan kamera.';
            }
        },

        clearPreview() {
            if (this.preview) URL.revokeObjectURL(this.preview);
            this.preview = null;
        },

        clearPhoto() {
            this.clearPreview();
            this.captured = false;
            this.$refs.photo.value = '';
            this.error = '';
        },

        destroy() {
            this.clearPreview();
        },
    };
}
