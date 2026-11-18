/**
 * Utilitas printer thermal Bluetooth (Web Bluetooth).
 * Dipakai bersama oleh halaman Kasir (cetak struk) dan Pengaturan > Printer.
 */

export const UUID_LAYANAN_PRINTER = [
    '000018f0-0000-1000-8000-00805f9b34fb',
    '0000ff00-0000-1000-8000-00805f9b34fb',
    '0000ffe0-0000-1000-8000-00805f9b34fb',
    '0000fff0-0000-1000-8000-00805f9b34fb',
    '0000fee7-0000-1000-8000-00805f9b34fb',
    '49535343-fe7d-4ae5-8fa9-9fafd205e455',
    'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
];

const KUNCI_PRINTER_TERSIMPAN = 'kasir-printer';

export function printerBisaDipakai() {
    return typeof navigator !== 'undefined' && 'bluetooth' in navigator;
}

export function simpanPrinterTersimpan(device) {
    if (typeof window === 'undefined' || !device) {
        return;
    }

    window.localStorage.setItem(
        KUNCI_PRINTER_TERSIMPAN,
        JSON.stringify({ id: device.id ?? null, name: device.name ?? null }),
    );
}

export function hapusPrinterTersimpan() {
    if (typeof window !== 'undefined') {
        window.localStorage.removeItem(KUNCI_PRINTER_TERSIMPAN);
    }
}

export function bacaPrinterTersimpan() {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const data = JSON.parse(window.localStorage.getItem(KUNCI_PRINTER_TERSIMPAN) ?? 'null');

        return data && (data.id || data.name) ? data : null;
    } catch {
        return null;
    }
}

export async function mintaPrinterBluetooth() {
    return navigator.bluetooth.requestDevice({
        acceptAllDevices: true,
        optionalServices: UUID_LAYANAN_PRINTER,
    });
}

/**
 * Cari printer Bluetooth yang sudah pernah diizinkan oleh pengguna
 * (Chrome menyimpan izin perangkat per situs), lalu cocokkan dengan
 * printer utama yang dipilih di Pengaturan > Printer.
 */
export async function printerTersimpan() {
    const tersimpan = bacaPrinterTersimpan();

    if (!tersimpan || !printerBisaDipakai() || !navigator.bluetooth.getDevices) {
        return null;
    }

    try {
        const daftar = await navigator.bluetooth.getDevices();

        return daftar.find((device) => device.id === tersimpan.id)
            ?? daftar.find((device) => device.name && device.name === tersimpan.name)
            ?? null;
    } catch {
        return null;
    }
}

export async function cariKarakteristikTulis(server) {
    const cariDiLayanan = async (layanan) => {
        const karakteristik = await layanan.getCharacteristics();

        return karakteristik.find((item) => item.properties.writeWithoutResponse)
            ?? karakteristik.find((item) => item.properties.write)
            ?? null;
    };

    for (const uuid of UUID_LAYANAN_PRINTER) {
        try {
            const layanan = await server.getPrimaryService(uuid);
            const karakteristik = await cariDiLayanan(layanan);

            if (karakteristik) {
                return karakteristik;
            }
        } catch {
            // Layanan tidak tersedia di printer ini, coba UUID berikutnya.
        }
    }

    try {
        const semuaLayanan = await server.getPrimaryServices();

        for (const layanan of semuaLayanan) {
            const karakteristik = await cariDiLayanan(layanan);

            if (karakteristik) {
                return karakteristik;
            }
        }
    } catch {
        // Biarkan ditangani sebagai karakteristik tidak ditemukan.
    }

    return null;
}

export async function kirimKePrinter(device, data) {
    const server = await device.gatt.connect();

    try {
        const karakteristik = await cariKarakteristikTulis(server);

        if (!karakteristik) {
            throw new Error('Printer tidak memiliki karakteristik tulis. Pastikan perangkat adalah printer thermal Bluetooth.');
        }

        const ukuranPotongan = 20;

        for (let posisi = 0; posisi < data.length; posisi += ukuranPotongan) {
            const potongan = data.slice(posisi, posisi + ukuranPotongan);

            if (karakteristik.properties.writeWithoutResponse) {
                await karakteristik.writeValueWithoutResponse(potongan);
            } else {
                await karakteristik.writeValue(potongan);
            }

            await new Promise((selesai) => setTimeout(selesai, 20));
        }
    } finally {
        if (device.gatt.connected) {
            device.gatt.disconnect();
        }
    }
}
