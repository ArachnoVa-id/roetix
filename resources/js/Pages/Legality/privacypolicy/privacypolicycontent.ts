export interface PrivacyPolicySection {
    id: string;
    title: string;
    content: string;
  }
  
  export interface PrivacyPolicyContent {
    title: string;
    lastUpdated: string;
    introduction: string;
    introductionNote: string;
    mainTitle: string;
    sections: PrivacyPolicySection[];
  }
  
  export const privacyPolicyContent: PrivacyPolicyContent = {
    title: 'Kebijakan Privasi',
    lastUpdated: '10 April 2025 • 14:00 • GMT +7',
    introduction:
      'Kebijakan Privasi ini mengatur dan/atau menjelaskan seluruh layanan yang sudah Kami sediakan untuk Anda ("Pengguna") gunakan, baik layanan yang Kami operasikan sendiri maupun yang dioperasikan melalui afiliasi dan/atau rekan Kami. Untuk menjaga kepercayaan Anda kepada Kami, maka Kami berkomitmen untuk senantiasa akan menjaga segala kerahasiaan yang terkandung dalam data pribadi Anda, karena Kami menganggap privasi Anda sangat penting bagi Kami.',
    introductionNote:
      'Kebijakan Privasi ini diperlukan untuk menjaga keamanan Data Pribadi Anda, mohon untuk membaca secara seksama seluruh ketentuan dalam Kebijakan Privasi ini.',
    mainTitle: 'Apa Itu Kebijakan Privasi NovaTix?',
    sections: [
      {
        id: 'info-kami-kumpulkan',
        title: 'Informasi Yang Kami Kumpulkan',
        content: `Saat Anda menggunakan layanan NovaTix, kami dapat mengumpulkan beberapa informasi berikut:

1.1 Pengguna Individu
- Informasi akun: Nama, alamat email, dan informasi Google yang Anda setujui untuk dibagikan saat login.
- Data transaksi: Detail pembelian tiket, jumlah pembayaran, dan metode pembayaran yang digunakan.
- Data preferensi: Riwayat pencarian acara dan interaksi Anda di platform untuk meningkatkan pengalaman pengguna.

1.2 Event Organizer (EO)
- Informasi bisnis: Nama organisasi/perusahaan, alamat email bisnis, dan kontak resmi.
- Data acara: Informasi tentang acara yang diunggah, termasuk lokasi, jadwal, harga tiket, dan deskripsi acara.
- Informasi pembayaran: Akun pembayaran yang digunakan untuk pencairan hasil penjualan tiket.`,
      },
      {
        id: 'penggunaan-info',
        title: 'Penggunaan Informasi',
        content: `Informasi yang kami kumpulkan digunakan semata-mata untuk tujuan berikut:

- Memproses transaksi pembelian tiket dan verifikasi pengguna.
- Memfasilitasi pengelolaan acara oleh EO.
- Mengirimkan konfirmasi tiket dan notifikasi terkait acara.
- Memberikan laporan analitik kepada EO untuk evaluasi acara.
- Meningkatkan layanan NovaTix melalui analisis penggunaan dan umpan balik pengguna.

Kami tidak akan menggunakan informasi pribadi Anda untuk tujuan lain tanpa persetujuan eksplisit dari Anda.`,
      },
      {
        id: 'pembayaran',
        title: 'Pembayaran',
        content: `Pembayaran tiket pada platform NovaTix diproses melalui layanan pihak ketiga, seperti Midtrans atau penyedia pembayaran lainnya. Kami tidak menyimpan atau memproses informasi pembayaran Anda secara langsung. Pastikan untuk membaca kebijakan privasi penyedia pembayaran untuk memahami bagaimana data pembayaran Anda dikelola.`,
      },
      {
        id: 'kebijakan-pengembalian',
        title: 'Kebijakan Pengembalian',
        content: `Mohon diperhatikan bahwa tidak ada pengembalian dana (refund) setelah pembelian tiket berhasil dilakukan. Pastikan Anda telah memeriksa detail pemesanan dengan benar sebelum menyelesaikan transaksi.`,
      },
      {
        id: 'keamanan-data',
        title: 'Keamanan Data',
        content: `Kami berkomitmen untuk menjaga keamanan informasi pribadi Anda. Namun, karena internet tidak sepenuhnya aman, kami menyarankan Anda untuk menjaga kredensial login dan akses akun Anda dengan hati-hati untuk menghindari risiko penyalahgunaan.`,
      },
      {
        id: 'pembagian-data-dengan-pihak-ketiga',
        title: 'Pembagian Data Dengan Pihak Ketiga',
        content: `Kami tidak menjual, menyewakan, atau memberikan informasi pribadi Anda kepada pihak ketiga kecuali dalam situasi berikut:
- Jika diwajibkan oleh hukum atau otoritas berwenang.
- Jika diperlukan untuk memproses transaksi pembayaran melalui mitra seperti Midtrans.
- Jika dibutuhkan untuk meningkatkan layanan dalam bentuk data anonim atau agregat.
`,
      },
      {
        id: 'hak-pengguna-dan-eo',
        title: 'Hak Pengguna dan EO',
        content: `Sebagai Pengguna dan EO, maka anda berhak untuk:
- Jika diwajibkan oleh hukum atau otoritas berwenang.
- Jika diperlukan untuk memproses transaksi pembayaran melalui mitra seperti Midtrans.
- Jika dibutuhkan untuk meningkatkan layanan dalam bentuk data anonim atau agregat.
`,
      },
      {
        id: 'penggunaan-data-oleh-google',
        title: 'Penggunaan Data oleh Google',
        content: `Jika Anda menggunakan autentikasi Google, maka Google dapat mengelola informasi tertentu dari akun Anda sesuai dengan kebijakan privasi Google. Kami menyarankan Anda untuk membaca kebijakan tersebut guna memahami bagaimana data Anda dikelola.
`,
      },
      {
        id: 'penggunaan-cookie-dan-teknologi-pelacakan',
        title: 'Penggunaan Cookie dan Teknologi Pelacakan',
        content: `Kami menggunakan cookie atau teknologi serupa untuk meningkatkan pengalaman pengguna, seperti menyimpan preferensi login atau mengoptimalkan tampilan situs. Anda dapat mengelola preferensi cookie melalui pengaturan browser Anda.
`,
      },
      {
        id: 'perubahan-kebijakan-privasi',
        title: 'Perubahan Kebijakan Privasi',
        content: `Kebijakan privasi ini dapat diperbarui dari waktu ke waktu. Perubahan akan diinformasikan melalui situs web kami atau melalui email. Kami menyarankan Anda untuk memeriksa kebijakan ini secara berkala.`,
      },
      {
        id: 'persetujuan',
        title: 'Persetujuan',
        content: `Dengan membaca seluruh ketentuan ini, mengakses, dan menggunakan layanan kami, maka Anda dianggap menyatakan telah diberitahukan, membaca, memahadmi, menyetujui, dan menyatakan tunduk atas kebijakan privasi ini beserta perubahan-perubahan yang mungkin Kami lakukan dari waktu ke waktu. Serta, anda menyatakan bahwa setiap Data Pribadi yang Anda berikan merupakan data yang benar dan sah.`,
      },
      {
        id: 'hukum-yang-berlaku',
        title: 'Hukum yang Berlaku',
        content: `Kebijakan privasi ini diatur dan ditafsirkan sesuai dengan hukum yang berlaku di Indonesia. Jika terjadi sengketa atau perselisihan terkait dengan kebijakan privasi ini, maka penyelesaiannya akan dilakukan melalui mekanisme hukum yang berlaku di Indonesia.`,
      },
      {
        id: 'hubungi-kami',
        title: 'Hubungi Kami',
        content: `Jika Anda memiliki pertanyaan atau memerlukan klarifikasi lebih lanjut mengenai kebijakan privasi ini, silakan hubungi kami melalui email atau media sosial resmi NovaTix.`,
      },
    ],
  };