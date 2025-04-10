export interface TermConditionSection {
    id: string;
    title: string;
    content: string;
  }
  
  export interface TermConditionContent {
    title: string;
    lastUpdated: string;
    // introduction: string;
    // introductionNote: string;
    mainTitle: string;
    sections: TermConditionSection[];
    footer: string;
  }
  
  export const termConditionContent: TermConditionContent = {
    title: 'Syarat dan Ketentuan',
    lastUpdated: '10 April 2025 • 14:00 • GMT +7',
    // introduction:
    //   'Selamat siang Bapak/Ibu/Saudara/I.\nTerima kasih atas ketertarikan Bapak/Ibu/Saudara/I dalam menghadiri Event **NovaTix**.',
    // introductionNote:
    //   'Demi kenyamanan bersama, kami mohon dapat memperhatikan dan mematuhi tata tertib yang berlaku selama event berlangsung.',
    mainTitle: 'Syarat dan Ketentuan NovaTix',
    sections: [
      {
        id: 'syarat-dan-ketentuan-novatix',
        title: 'Syarat dan Ketentuan NovaTix',
        content: `Selamat datang di NovaTix! Terimakasih telah menyempatkan waktu untuk mempelajari segala ketentuan yang berkaitan dengan NovaTix.

Dengan memanfaatkan layanan kami, Anda dianggap telah menerima dan terikat sepenuhnya oleh seluruh syarat serta ketentuan yang berlaku. Jika keberatan terhadap ketentuan tersebut, sangat disarankan untuk menghentikan proses transaksi dengan pihak kami.

**Definisi**
1. **“NovaTix”**: Platform penyedia layanan pembelian tiket online dan manajemen acara.
2. **“Pengguna”**: Setiap individu yang menggunakan NovaTix untuk membeli tiket.
3. **“Event Organizer (EO)”**: Pihak penyelenggara acara yang menggunakan NovaTix untuk menjual tiket dan mengelola event.
4. **“Pihak Ketiga”**: Mitra pembayaran, layanan keamanan, dan pihak lain yang bekerja sama dengan NovaTix.
5. **“Anda”**: Pengguna atau Event organizer sebagai pengguna pelayanan NovaTix

**Ketentuan Umum**

**1. Ruang Lingkup Layanan**
NovaTix bertindak sebagai perantara dalam transaksi pemesanan tiket antara pengguna dan EO. Layanan yang tersedia mencakup pencarian tiket, pembelian, pembayaran, serta fitur manajemen acara bagi EO. Semua informasi terkait acara berasal dari EO dan pihak ketiga yang bekerja sama dengan NovaTix.

**2. Pendaftaran dan Akun Pengguna**
Setiap pengguna dan EO wajib mendaftarkan akun untuk menggunakan layanan NovaTix. Informasi yang diberikan saat pendaftaran harus akurat, lengkap, dan selalu diperbarui. NovaTix berhak menolak pendaftaran atau menonaktifkan akun jika ditemukan pelanggaran terhadap ketentuan ini.

**3. Hak dan Kewajiban Pengguna**
Pengguna wajib menggunakan layanan NovaTix sesuai dengan hukum yang berlaku dan tidak melakukan aktivitas yang merugikan NovaTix, EO, atau pengguna lain. Pengguna bertanggung jawab atas keamanan akun dan data yang diberikan serta tidak diperkenankan menyalahgunakan informasi dari platform.

**4. Hak dan Kewajiban Event Organizer (EO)**
EO bertanggung jawab atas informasi acara yang disediakan dan wajib memastikan keakuratan detail tiket yang dijual. EO juga harus mengelola transaksi secara transparan, termasuk kebijakan refund dan pembatalan acara. Semua acara yang diselenggarakan harus mematuhi hukum yang berlaku.

**5. Biaya dan Pembayaran**
NovaTix dapat mengenakan biaya layanan yang akan diinformasikan sebelum transaksi dilakukan. Semua pembayaran dilakukan melalui metode yang disediakan oleh NovaTix. EO bertanggung jawab atas transaksi yang mereka kelola, termasuk penerimaan dan pengembalian dana jika diperlukan.

**6. Pembatalan dan Pengembalian Dana (Refund)**
Tiket yang telah dibeli tidak dapat dikembalikan atau dibatalkan, kecuali jika ada kebijakan refund dari EO. Jika acara dibatalkan, refund akan diproses sesuai dengan kebijakan yang ditetapkan oleh EO dan NovaTix.

**7. Privasi dan Keamanan Data**
NovaTix melindungi data pengguna sesuai dengan Kebijakan Privasi yang berlaku. Pengguna dan EO dilarang menyebarkan data yang diperoleh dari NovaTix tanpa izin.

**8. Pembatasan Tanggung Jawab**
NovaTix bukan penyelenggara acara dan tidak bertanggung jawab atas kegagalan EO dalam menyelenggarakan event. Kesalahan informasi yang diberikan oleh EO sepenuhnya menjadi tanggung jawab EO. Segala risiko dalam menghadiri acara ditanggung oleh pengguna.

**9. Pelanggaran dan Sanksi**
NovaTix berhak menangguhkan atau menghapus akun pengguna dan EO yang melanggar ketentuan ini. Setiap penyalahgunaan layanan dapat dikenakan sanksi hukum sesuai dengan peraturan perundang-undangan yang berlaku di Indonesia.

**10. Hukum yang Berlaku dan Penyelesaian Sengketa**
Ketentuan ini tunduk pada hukum yang berlaku di Indonesia. Jika terjadi sengketa, penyelesaian akan dilakukan melalui jalur hukum atau mediasi yang disepakati kedua belah pihak.

Ketentuan umum ini dapat berubah dari waktu ke waktu. Kami akan selalu berupaya untuk memberikan pelayanan terbaik bagi Anda, sehingga perubahan yang kami lakukan bertujuan untuk memberikan pelayanan terbaik. Oleh karena itu, sangat disarankan bagi Anda untuk memahami dan terus meninjau secara berkala Ketentuan Umum dari kami.
`,
      },
      {
        id: 'tanggung-jawab-novatix',
        title: 'Tanggung Jawab NovaTix',
        content: `**1. Penyediaan Layanan**
NovaTix bertanggung jawab untuk menyediakan platform yang memungkinkan pengguna dan EO melakukan transaksi tiket secara aman dan efisien. Kami akan berusaha menjaga ketersediaan layanan dan meningkatkan fitur sesuai kebutuhan pengguna.

**2. Keamanan Data Pengguna**
NovaTix berkomitmen untuk melindungi informasi pribadi pengguna sesuai dengan Kebijakan Privasi yang berlaku. Kami menggunakan standar keamanan yang sesuai untuk mencegah akses tidak sah terhadap data pengguna.

**3. Dukungan dan Bantuan**
NovaTix menyediakan layanan pelanggan untuk menangani pertanyaan, keluhan, atau kendala teknis yang dialami pengguna. Kami akan berusaha memberikan solusi terbaik sesuai dengan kebijakan yang telah ditetapkan.

**4. Transparansi Biaya dan Ketentuan**
NovaTix akan memberikan informasi yang jelas mengenai biaya layanan, metode pembayaran, serta ketentuan pembatalan dan refund sebelum pengguna melakukan transaksi.

**5. Pengelolaan Perselisihan**
Jika terjadi perselisihan antara pengguna dan EO terkait transaksi tiket, NovaTix akan berperan sebagai mediator untuk membantu menyelesaikan masalah sesuai dengan kebijakan yang berlaku.

**6. Pembaruan Kebijakan dan Ketentuan**
NovaTix berhak melakukan perubahan terhadap Ketentuan Layanan sesuai dengan perkembangan hukum dan kebijakan internal. Pengguna akan diinformasikan mengenai perubahan tersebut melalui platform atau media komunikasi resmi NovaTix.
`
      },
      {
        id: 'tanggung-jawab-pengguna',
        title: 'Tanggung Jawab Pengguna',
        content: `**1. Keakuratan Data**
Pengguna wajib memberikan informasi yang benar dan lengkap saat melakukan pendaftaran akun atau pembelian tiket.

**2. Keamanan Akun**
Pengguna bertanggung jawab atas keamanan akun mereka sendiri, termasuk menjaga kerahasiaan kata sandi dan informasi login.

**3. Kepatuhan terhadap Kebijakan**
Pengguna harus menggunakan layanan NovaTix dengan itikad baik dan tidak melakukan tindakan yang dapat merugikan pihak lain, seperti penyalahgunaan data atau penipuan.

**4. Penerimaan Syarat dan Ketentuan**
Dengan menggunakan layanan NovaTix, pengguna dianggap telah membaca, memahami, dan menyetujui Ketentuan Umum serta kebijakan yang berlaku di platform.

**5. Pemantauan Informasi Acara**
Pengguna bertanggung jawab untuk memastikan detail acara sebelum membeli tiket, termasuk membaca kebijakan refund dan pembatalan yang berlaku untuk setiap event.
`
      },
      {
        id: 'tanggung-jawab-eo',
        title: 'Tanggung Jawab Event Organizer (EO)',
        content: `**1. Keabsahan Acara**
EO wajib memastikan bahwa acara yang mereka unggah di NovaTix adalah sah dan sesuai dengan hukum yang berlaku di Indonesia.

**2. Keakuratan Informasi Acara**
EO bertanggung jawab atas informasi yang disediakan, termasuk tanggal, lokasi, harga tiket, serta syarat dan ketentuan yang berlaku untuk acara tersebut.

**3. Hak dan Kewajiban yang Tidak Dialihkan**
EO tidak diperkenankan mengalihkan hak atau kewajibannya dalam menggunakan layanan NovaTix kepada pihak lain tanpa persetujuan tertulis dari NovaTix.

**4. Manajemen Tiket dan Transaksi**
EO harus memastikan bahwa sistem penjualan tiket berjalan dengan baik, termasuk menangani transaksi, konfirmasi tiket, dan kebijakan refund jika diperlukan.

**5. Penyelesaian Masalah dengan Pengguna**
Jika ada keluhan dari pembeli tiket, EO bertanggung jawab untuk menanganinya dengan solusi yang adil sesuai dengan kebijakan yang telah ditetapkan.

**6. Kepatuhan terhadap Kebijakan NovaTix**
EO harus mengikuti semua ketentuan dan kebijakan yang ditetapkan oleh NovaTix untuk memastikan pengalaman pengguna yang aman dan nyaman.

Jika terjadi pelanggaran terhadap tanggung jawab ini, NovaTix berhak mengambil tindakan yang diperlukan, termasuk penangguhan atau penghapusan akun pengguna maupun EO yang terbukti melanggar ketentuan.
`
      },
      {
        id: 'penyelesaian-sengketa',
        title: 'Penyelesaian Sengketa',
        content: `**1. Upaya Penyelesaian Secara Damai**
Setiap sengketa yang timbul antara pengguna, Event Organizer (EO), dan NovaTix akan diselesaikan terlebih dahulu melalui musyawarah untuk mencapai kesepakatan yang adil bagi semua pihak.

**2. Pelaporan Sengketa**
Pengguna atau EO yang mengalami permasalahan terkait transaksi, layanan, atau kebijakan NovaTix dapat mengajukan keluhan melalui layanan pelanggan NovaTix dalam waktu maksimal 7 (tujuh) hari kerja sejak peristiwa yang menjadi dasar sengketa terjadi.

**3. Mekanisme Mediasi oleh NovaTix**
a. NovaTix akan melakukan investigasi terhadap laporan yang masuk dalam waktu maksimal 14 (empat belas) hari kerja.
b. Jika ditemukan pelanggaran atau ketidaksesuaian, NovaTix akan memberikan rekomendasi penyelesaian yang harus dipatuhi oleh kedua belah pihak.
c. Jika tidak ada kesepakatan yang tercapai melalui mediasi, NovaTix akan memberikan keputusan akhir berdasarkan kebijakan yang berlaku.

**4. Penyelesaian melalui Jalur Hukum**
a. Jika sengketa tidak dapat diselesaikan melalui mediasi internal, para pihak dapat menempuh jalur hukum sesuai dengan peraturan perundang-undangan yang berlaku di Indonesia.
b. Semua perselisihan yang tidak dapat diselesaikan secara damai akan diselesaikan melalui Pengadilan Negeri Yogyakarta, kecuali disepakati lain oleh para pihak yang bersengketa.

**5. Penangguhan atau Pembekuan Akun**
Jika sengketa terkait dugaan pelanggaran kebijakan, penyalahgunaan layanan, atau tindakan merugikan pengguna lain, NovaTix berhak melakukan penangguhan sementara atau pembekuan permanen terhadap akun pengguna atau EO yang bersangkutan sampai permasalahan diselesaikan.

**6. Pengembalian Dana dan Ganti Rugi**
a. Pengembalian dana atau kompensasi hanya diberikan jika terbukti ada kelalaian dari pihak NovaTix atau EO dalam menyediakan layanan sesuai dengan ketentuan yang telah disepakati.
b. Pengguna tidak dapat menuntut ganti rugi di luar ketentuan yang telah ditetapkan dalam kebijakan refund dan ketentuan penggunaan NovaTix.

**7. Keputusan Bersifat Final**
Keputusan yang diambil NovaTix dalam proses penyelesaian sengketa bersifat final dan mengikat, kecuali pengguna atau EO memilih untuk melanjutkan penyelesaian melalui jalur hukum sesuai dengan poin 4.

Dengan demikian ketentuan Penyelesaian Sengketa Kami buat dan ketentuan ini dapat berubah dari waktu ke waktu. Dengan menggunakan layanan NovaTix, pengguna dan EO dianggap telah membaca, memahami, dan menyetujui mekanisme ketentuan ini.
`
      },
    ],
    footer: 'Bila ada pertanyaan lebih lanjut, silakan menghubungi kami melalui Direct Message Instagram **@arachnova.id**.\n\nAtas perhatian dan kerja sama, kami ucapkan terima kasih.\n\nHormat kami,\n **NovaTix**'
  };