Joob-seeker · Laravel 13 + Inertia + React

# Flowchart JobPilot AI

Alur sistem JobPilot dari registrasi sampai lamaran terkirim: pencarian lowongan di background, skor kecocokan, CV yang disesuaikan, dan gerbang persetujuan berbasis content hash. Setiap diagram punya tombol untuk menyalin kode Mermaid-nya.

Aksi pengguna Proses sistem Agent AI / LLM Database Ditolak / gagal Selesai

[1. Alur utama](#utama) [2. Discovery & matching](#workflow) [3. Generate draft](#draft) [4. Approve & send](#approval) [5. Status draft](#status)

## 01Alur utama pengguna

Gambaran end-to-end: akun dibuat lewat Fortify, pengguna mengisi profil kandidat, menjalankan workflow pencarian, lalu memilih lowongan untuk dibuatkan draft lamaran yang wajib direview sebelum dikirim.

`routes/web.php``CreateNewUser``pages/*`

```mermaid
flowchart TD
  A(["Mulai"]) --> B{"Sudah punya akun?"}
  B -- Tidak --> C["Register via Fortify"]
  B -- Ya --> D["Login"]
  D --> D2{"2FA aktif?"}
  D2 -- Ya --> D3["Masukkan kode 2FA"]
  D2 -- Tidak --> E
  D3 --> E
  C --> E["Dashboard"]
  E --> F["Isi Candidate Profileskills, pendidikan, pengalaman,preferensi lokasi & tipe kerja"]
  F --> G["Jalankan workflowkeywords, negara, tipe kerja, max jobs"]
  G --> H[["RunJobWorkflow di queuediscovery + matching"]]
  H --> I["Halaman Jobslowongan + match score"]
  I --> J{"Ada lowongan cocok?"}
  J -- Belum --> G
  J -- Ya --> K[["Generate draftCV tailoring + email"]]
  K --> L["Halaman Review"]
  L --> M{"Keputusan pengguna"}
  M -- Edit --> N["Simpan editversi naik, hash baru"]
  N --> L
  M -- Reject --> O["Draft rejected"]
  O --> I
  M -- Approve --> P["Application approvedterikat content hash"]
  P --> Q["Halaman Applicationsklik Send"]
  Q --> R{"JOBPILOT_SEND_EMAILS?"}
  R -- false --> S["Safe modeapproval diverifikasi,email tidak dikirim"]
  R -- true --> T["Kirim lewat mailerCV terlampir"]
  T --> U(["Lamaran terkirim"])

  classDef user fill:#e6efff,stroke:#3b6fd8,color:#16306b
  classDef sys fill:#eef0f5,stroke:#6b7387,color:#1a1f2e
  classDef ai fill:#fdf0d9,stroke:#c27a0e,color:#5c3a05
  classDef done fill:#d9f2e3,stroke:#1f7a45,color:#0f3d22
  classDef bad fill:#fde4e1,stroke:#c2412d,color:#6b1d12
  class C,D,D3,F,G,L,N,Q user
  class E,I,P,S sys
  class H,K ai
  class T,U done
  class O bad
```

## 02Workflow discovery & matching

Berjalan sebagai queue job, jadi queue worker harus hidup. Lowongan disimpan bersama untuk semua user, sedangkan hasil matching milik masing-masing user. Skor deterministik adalah acuan utama; analisis LLM hanya pelengkap.

`WorkflowController``RunJobWorkflow``JobDiscoveryAgent``JobMatchingAgent`

```mermaid
flowchart TD
  A["POST /workflowsvalidasi StoreWorkflowRequestthrottle 10/menit"] --> B[("WorkflowRun dibuatstatus pending, stage queued")]
  B --> C["Dispatch RunJobWorkflow ke queue"]
  C --> D["status runningstage discovery"]
  D --> E{"Preferensi sumber"}
  E -- "auto + key Adzuna ada" --> F["AdzunaJobSource"]
  E -- "mock / tanpa key" --> G["MockJobSource"]
  F --> H{"Negara didukung Adzuna?"}
  H -- Tidak --> G2["Pakai sample listingscatat warning"]
  H -- Ya --> I["Cari per negara x tipe kerja"]
  G --> I
  G2 --> I
  I --> J["Normalisasi + dedupberdasarkan source + source_idbatasi max_jobs"]
  J --> K[("job_listingsupdateOrCreate, shared")]
  K --> L{"run_matching?"}
  L -- Tidak --> P
  L -- Ya --> M["stage matchingJobMatchingAgent"]
  M --> N["Skor berbobotskill 35% · keyword 20%tipe kerja 15% · lokasi 15%pendidikan 10% · pengalaman 5%"]
  N --> O{"Ollama tersedia?"}
  O -- Ya --> O1["Penjelasan dari LLM"]
  O -- Tidak --> O3["Fallback engine offline"]
  O1 --> Q[("job_matchesper user")]
  O3 --> Q
  Q --> P(["status completedstage done + warnings"])
  D -. exception .-> X["status failedsimpan error_message"]
  M -. exception .-> X

  classDef user fill:#e6efff,stroke:#3b6fd8,color:#16306b
  classDef sys fill:#eef0f5,stroke:#6b7387,color:#1a1f2e
  classDef ai fill:#fdf0d9,stroke:#c27a0e,color:#5c3a05
  classDef db fill:#e3f5ea,stroke:#2f8a55,color:#123d24
  classDef done fill:#d9f2e3,stroke:#1f7a45,color:#0f3d22
  classDef bad fill:#fde4e1,stroke:#c2412d,color:#6b1d12
  class A user
  class C,D,F,G,G2,I,J sys
  class M,N,O1,O3 ai
  class B,K,Q db
  class P done
  class X bad
```

## 03Generate draft lamaran

CV hanya boleh diurutkan ulang dan disesuaikan bahasanya. Setiap skill atau entri yang tidak ada di profil dibuang oleh validateClaims. Alamat penerima hanya diambil dari email yang tercantum di lowongan, tidak pernah ditebak.

`ApplicationDraftController@store``CvTailoringAgent``EmailApplicationAgent`

```mermaid
flowchart TD
  A["POST /jobs/{jobListing}/draftsthrottle 20/menit"] --> B{"Draft pending untuklowongan ini sudah ada?"}
  B -- Ya --> R(["Buka di halaman Review"])
  B -- Tidak --> C["Ambil evidence dariCandidate Profile"]
  C --> D["LLM menyusun ulang CVtanpa menambah fakta"]
  D --> D2{"Output JSON valid?"}
  D2 -- Tidak --> D3["Pakai data profil apa adanya"]
  D2 -- Ya --> E["validateClaimsbuang skill & entriyang tidak ada di profil"]
  D3 --> E
  E --> F{"dompdf terpasang?"}
  F -- Ya --> G["Simpan CV PDF + sha256"]
  F -- Tidak --> G2["Simpan CV HTML + sha256"]
  G --> H["Draft subject & body email"]
  G2 --> H
  H --> I{"Lowongan punyaapplication_email?"}
  I -- Ya --> J["Recipient terisi, verified"]
  I -- Tidak --> J2["Recipient kosongpengguna wajib mengisi"]
  J --> K[("Hitung content hashsimpan draft v1")]
  J2 --> K
  K --> R

  classDef user fill:#e6efff,stroke:#3b6fd8,color:#16306b
  classDef sys fill:#eef0f5,stroke:#6b7387,color:#1a1f2e
  classDef ai fill:#fdf0d9,stroke:#c27a0e,color:#5c3a05
  classDef db fill:#e3f5ea,stroke:#2f8a55,color:#123d24
  classDef done fill:#d9f2e3,stroke:#1f7a45,color:#0f3d22
  class A user
  class C,D3,G,G2,J,J2 sys
  class D,E,H ai
  class K db
  class R done
```

## 04Approve & send

Gerbang keamanan inti. Approval mengikat hash dari recipient, subject, body, dan CV yang persis dilihat pengguna. Saat Send, hash dihitung ulang; kalau ada perubahan sejak approval, pengiriman ditolak.

`ApplicationDraftController@approve``ApplicationController@send``ApplicationDispatcher`

```mermaid
flowchart TD
  A["Klik Approvemengirim content_hash di layar"] --> B{"Hash sama denganhash draft saat ini?"}
  B -- Tidak --> X1["Ditolak: draft berubahreload & review ulang"]
  B -- Ya --> C{"Draft sudah sent?"}
  C -- Ya --> X2["Ditolak: sudah terkirim"]
  C -- Tidak --> D{"recipient_email ada?"}
  D -- Tidak --> X3["Ditolak: isi recipient dulu"]
  D -- Ya --> E[("Transaksi DBApprovalRecord dibuatdraft approvedApplication approved + sent_content_hash")]
  E --> F["Klik Send di halaman Applications"]
  F --> G{"Draft approved danhash masih cocok?"}
  G -- Tidak --> X4["Ditolak: perlu approve ulang"]
  G -- Ya --> H{"JOBPILOT_SEND_EMAILS?"}
  H -- false --> S["Safe modetidak ada email keluar"]
  H -- true --> I["status sending"]
  I --> J{"Mailer berhasil?"}
  J -- Ya --> K(["status sentsimpan message id"])
  J -- Tidak --> L["status uncertainsimpan error"]

  classDef user fill:#e6efff,stroke:#3b6fd8,color:#16306b
  classDef sys fill:#eef0f5,stroke:#6b7387,color:#1a1f2e
  classDef db fill:#e3f5ea,stroke:#2f8a55,color:#123d24
  classDef done fill:#d9f2e3,stroke:#1f7a45,color:#0f3d22
  classDef bad fill:#fde4e1,stroke:#c2412d,color:#6b1d12
  class A,F user
  class S,I sys
  class E db
  class K done
  class X1,X2,X3,X4,L bad
```

## 05Siklus status draft

Draft yang sudah approved atau sent tidak bisa diedit. Draft yang ditolak masih bisa diedit dan kembali ke status draft dengan versi baru.

`ApplicationDraft``Application`

```mermaid
stateDiagram-v2
  [*] --> draft : generate draft
  draft --> draft : edit (versi naik, hash baru)
  draft --> approved : approve (hash cocok)
  draft --> rejected : reject
  rejected --> draft : edit
  approved --> rejected : reject
  approved --> sent : send berhasil
  approved --> uncertain : send gagal (Application)
  sent --> [*]
```

Disusun dari kode di folder Joob-seeker (routes, controllers, services JobPilot, dan RunJobWorkflow) per 20 Oktober 2026.