<template>
    <div class="bank-page">
      <header class="page-header">
        <nav class="breadcrumb">
          <router-link to="/ujian-online/bank-soal">Bank Soal</router-link>
          <span v-if="currentBank" class="sep">/</span>
          <span v-if="currentBank" class="current">{{ currentBank.name || currentBank.code }}</span>
        </nav>
        <h2>{{ currentBank ? (currentBank.name || 'Soal: ' + currentBank.code) : 'Soal' }}</h2>
        <div class="header-actions">
          <router-link v-if="bankId" :to="`/ujian-online/bank-soal/${bankId}/stimulus`" class="btn-secondary btn-link-stimulus">Kelola Stimulus</router-link>
          <button v-if="bankId" type="button" class="btn-secondary" @click="openImportModal">Import Excel</button>
          <select v-if="!bankId" v-model="filterSubjectId" @change="fetchQuestions(1)">
            <option value="">Semua mapel</option>
            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
          </select>
          <button class="btn-primary" @click="openForm()">+ Tambah Soal</button>
        </div>
      </header>

      <div v-if="!bankId" class="content-card info-box">
        <p>Pilih bank soal dari daftar, lalu klik <strong>Kelola Soal</strong> untuk mengisi soal.</p>
        <router-link to="/ujian-online/bank-soal" class="btn-primary">Daftar Bank Soal</router-link>
      </div>

      <template v-else>
        <ListFilterBar class="filters-bar content-card">
          <input
            v-model="filterSearch"
            type="search"
            class="filter-search"
            placeholder="Cari teks soal..."
            autocomplete="off"
            @keydown.enter.prevent="fetchQuestions(1)"
          />
          <select v-model="filterType" class="filter-select" @change="fetchQuestions(1)">
            <option value="">Semua tipe</option>
            <option value="pg">Pilihan ganda</option>
            <option value="pg_kompleks">PG kompleks</option>
            <option value="matching">Mencocokkan</option>
            <option value="isian">Isian</option>
            <option value="uraian">Uraian</option>
          </select>
          <select v-model="filterStatus" class="filter-select" @change="fetchQuestions(1)" aria-label="Filter status">
            <option value="">Semua status</option>
            <option value="ready">Siap</option>
            <option value="draft">Draf</option>
          </select>
          <select v-model="filterTopic" class="filter-select" @change="fetchQuestions(1)" aria-label="Filter topik">
            <option value="">Semua topik</option>
            <option value="none">Tanpa topik</option>
            <option v-for="topic in topicOptions" :key="topic" :value="topic">{{ topic }}</option>
          </select>
          <select v-model="filterCognitiveLevel" class="filter-select" @change="fetchQuestions(1)" aria-label="Filter level kognitif">
            <option value="">Semua level</option>
            <option value="none">Tanpa level</option>
            <option v-for="opt in cognitiveLevelOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          <select v-model="filterDifficulty" class="filter-select" @change="fetchQuestions(1)" aria-label="Filter kesulitan">
            <option value="">Semua kesulitan</option>
            <option value="none">Tanpa kesulitan</option>
            <option v-for="opt in difficultyOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
          <input
            v-model="filterTag"
            type="search"
            class="filter-search filter-tag"
            placeholder="Tag, mis. aljabar"
            @keydown.enter.prevent="fetchQuestions(1)"
          />
          <select v-model="filterStimulusId" class="filter-select" @change="fetchQuestions(1)">
            <option value="">Semua stimulus</option>
            <option value="none">Tanpa stimulus</option>
            <option v-for="st in stimuli" :key="st.id" :value="String(st.id)">{{ stimulusOptionLabel(st) }}</option>
          </select>
          <button type="button" class="btn-secondary" @click="fetchQuestions(1)">Cari</button>
          <button v-if="hasActiveFilters" type="button" class="btn-secondary" @click="clearFilters">Reset</button>
        </ListFilterBar>

        <div v-if="filterStimulusId && filterStimulusId !== 'none'" class="stimulus-banner content-card">
          <div>
            <strong>Filter stimulus:</strong> {{ selectedStimulusLabel }}
            <span class="banner-hint">— satu stimulus bisa dipakai banyak soal. Tambah soal baru akan memakai stimulus ini.</span>
          </div>
          <button type="button" class="btn-primary btn-sm" @click="openForm(null, { preferStimulus: filterStimulusId })">
            + Soal dengan stimulus ini
          </button>
        </div>

        <div v-if="loading" class="content-card"><p>Memuat...</p></div>
        <div v-else-if="questions.length === 0" class="empty-state content-card">
          <h3>{{ hasActiveFilters ? 'Tidak ada soal yang cocok' : 'Belum ada soal' }}</h3>
          <p v-if="hasActiveFilters">Ubah filter atau reset pencarian.</p>
          <p v-else>Tambahkan soal ke bank ini. Stimulus opsional — satu stimulus boleh dipakai beberapa soal.</p>
          <button class="btn-primary" @click="openForm()">Tambah Soal</button>
        </div>
        <div v-else class="table-wrap content-card">
          <p class="reorder-hint">Urutan di rak ini. Ujian memilih soal sendiri — boleh campur dari rak/tingkat lain. Edit di sini tidak mengubah paket ujian yang sudah dipasang.</p>
          <div v-if="selectedIds.length" class="bulk-bar">
            <span>{{ selectedIds.length }} soal dipilih</span>
            <button type="button" class="btn-secondary btn-sm" :disabled="bulkLoading" @click="bulkDuplicate">Duplikat</button>
            <button type="button" class="btn-danger btn-sm" :disabled="bulkLoading" @click="bulkDelete">Hapus</button>
            <button type="button" class="btn-secondary btn-sm" @click="selectedIds = []">Batal</button>
          </div>
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-check">
                  <input type="checkbox" :checked="pageAllSelected" aria-label="Pilih semua di halaman ini" @change="togglePage($event.target.checked)" />
                </th>
                <th class="col-ord">Urut</th>
                <th>Soal</th>
                <th>Stimulus</th>
                <th>Tipe</th>
                <th>Status</th>
                <th>Topik</th>
                <th>Level</th>
                <th>Kesulitan</th>
                <th>Bobot</th>
                <th>Dipakai</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(q, idx) in questions"
                :key="q.id"
                :class="{ 'drag-over': dragOverIndex === idx, dragging: dragFromIndex === idx }"
                @dragover.prevent="onDragOver($event, idx)"
                @drop="onDrop($event, idx)"
              >
                <td class="col-check">
                  <input type="checkbox" :checked="selectedIds.includes(q.id)" :aria-label="'Pilih soal ' + (idx + 1)" @change="toggleSelected(q.id, $event.target.checked)" />
                </td>
                <td class="col-ord">
                  <span class="drag-handle" draggable="true" title="Seret untuk mengubah urutan" @dragstart="onDragStart($event, idx)" @dragend="onDragEnd">⋮⋮</span>
                  <div class="move-buttons">
                    <button type="button" class="btn-move" title="Naik" :disabled="reorderLoading || idx === 0" @click="moveQuestion(idx, -1)">↑</button>
                    <button type="button" class="btn-move" title="Turun" :disabled="reorderLoading || idx === questions.length - 1" @click="moveQuestion(idx, 1)">↓</button>
                  </div>
                </td>
                <td>
                  {{ stripHtml((q.body || '')).slice(0, 80) }}{{ stripHtml(q.body || '').length > 80 ? '…' : '' }}
                  <div v-if="q.tags?.length" class="tag-row">
                    <span v-for="tag in q.tags" :key="tag" class="tag-chip">{{ tag }}</span>
                  </div>
                </td>
                <td>
                  <button
                    v-if="q.stimulus"
                    type="button"
                    class="stimulus-chip"
                    :title="'Filter soal dengan stimulus ini'"
                    @click="filterByStimulus(q.stimulus_id)"
                  >
                    {{ q.stimulus.title || stripHtml(q.stimulus.content || '').slice(0, 30) + '…' }}
                  </button>
                  <span v-else>–</span>
                </td>
                <td>{{ typeLabel(q.type) }}</td>
                <td>
                  <span class="status-chip" :class="q.status === 'draft' ? 'draft' : 'ready'">{{ q.status === 'draft' ? 'Draf' : 'Siap' }}</span>
                </td>
                <td>{{ q.topic || '—' }}</td>
                <td>
                  <span v-if="q.cognitive_level" class="level-chip" :title="cognitiveLevelLabel(q.cognitive_level)">{{ String(q.cognitive_level).toUpperCase() }}</span>
                  <span v-else class="used-none">—</span>
                </td>
                <td>{{ q.difficulty_label || '—' }}</td>
                <td>{{ q.weight }}</td>
                <td>
                  <span v-if="(q.exams_count || 0) > 0" class="used-chip" :title="'Terpasang di ' + q.exams_count + ' ujian. Paket ujian tidak berubah jika soal diedit.'">
                    {{ q.exams_count }} ujian
                  </span>
                  <span v-else class="used-none">—</span>
                </td>
                <td class="row-actions">
                  <TableAction kind="preview" @click="openPreview(q)" />
                  <TableAction kind="edit" @click="openForm(q)" />
                  <TableAction kind="duplicate" :disabled="duplicatingId === q.id" @click="duplicateQuestion(q)" />
                  <TableAction kind="delete" @click="confirmDelete(q)" />
                </td>
              </tr>
            </tbody>
          </table>
          <PaginationBar
            v-if="pagination"
            :page="pagination.current_page"
            :last-page="pagination.last_page"
            :per-page="pagination.per_page"
            :total="pagination.total"
            item-label="soal"
            @page-change="fetchQuestions"
            @per-page-change="changePerPage"
          />
        </div>
      </template>

      <!-- Form tambah/edit -->
      <div v-if="showForm" class="modal-overlay" @click.self="showForm = false">
        <div class="modal content-card modal-question-form">
          <header class="qform-header">
            <div>
              <h3>{{ editingId ? 'Edit soal' : 'Tambah soal' }}</h3>
              <p v-if="currentBank" class="qform-meta">
                {{ currentBank.subject?.name || 'Mapel' }}
                <span v-if="currentBank.grade"> · kelas {{ currentBank.grade }}</span>
              </p>
            </div>
            <button type="button" class="btn-icon-close" title="Tutup" @click="showForm = false">×</button>
          </header>

          <div class="qform-body">
            <section class="qform-section">
              <h4 class="qform-section-title">Pengaturan</h4>
              <div v-if="!bankId" class="form-group">
                <label>Mapel *</label>
                <select v-model="form.subject_id" required>
                  <option value="">Pilih</option>
                  <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
              </div>
              <div class="qform-grid">
                <div class="form-group">
                  <label>Tipe soal *</label>
                  <select v-model="form.type">
                    <option value="pg">Pilihan ganda</option>
                    <option value="pg_kompleks">Pilihan ganda kompleks</option>
                    <option value="matching">Mencocokkan</option>
                    <option value="isian">Isian singkat</option>
                    <option value="uraian">Uraian</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Bobot</label>
                  <input
                    v-if="form.type !== 'pg_kompleks'"
                    v-model.number="form.weight"
                    type="number"
                    min="0"
                    step="0.01"
                  />
                  <p v-else class="hint field-hint">Otomatis dari jumlah bobot opsi benar.</p>
                  <p class="hint field-hint">Bobot relatif. Nilai ujian dinormalisasi ke 0–100.</p>
                </div>
                <div class="form-group">
                  <label>Status</label>
                  <select v-model="form.status">
                    <option value="ready">Siap — boleh masuk paket ujian</option>
                    <option value="draft">Draf — belum untuk ujian</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Topik / bab <span class="optional">(opsional)</span></label>
                  <input v-model="form.topic" type="text" maxlength="120" list="topic-list" placeholder="mis. Bab 2 Persamaan linear" />
                  <datalist id="topic-list">
                    <option v-for="topic in topicOptions" :key="topic" :value="topic" />
                  </datalist>
                </div>
                <div class="form-group">
                  <label>Level kognitif <span class="optional">(opsional)</span></label>
                  <select v-model="form.cognitive_level">
                    <option value="">—</option>
                    <option v-for="opt in cognitiveLevelOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Kesulitan <span class="optional">(opsional)</span></label>
                  <select v-model="form.difficulty">
                    <option value="">—</option>
                    <option v-for="opt in difficultyOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Tag <span class="optional">(opsional)</span></label>
                  <input v-model="form.tagsText" type="text" maxlength="200" placeholder="pisahkan dengan koma, mis. aljabar, persamaan" />
                </div>
              </div>
              <div class="form-group">
                <label>Stimulus <span class="optional">(opsional)</span></label>
                <div class="stimulus-row">
                  <select v-model="form.stimulus_id">
                    <option value="">Tanpa stimulus</option>
                    <option v-for="st in filteredStimuli" :key="st.id" :value="String(st.id)">{{ stimulusOptionLabel(st) }}</option>
                  </select>
                  <button type="button" class="btn-secondary btn-add-stimulus" @click="openStimulusForm">+ Stimulus baru</button>
                </div>
                <p class="hint">Satu stimulus bisa dipakai banyak soal.</p>
              </div>
            </section>

            <section class="qform-section">
              <h4 class="qform-section-title">Pertanyaan *</h4>
              <div class="form-group form-group-flush">
                <RichTextEditor v-model="form.body" placeholder="Tulis pertanyaan (teks, gambar, tabel, rumus)" min-height="160px" />
              </div>
            </section>

            <section v-if="form.type === 'pg'" class="qform-section">
              <div class="qform-section-head">
                <h4 class="qform-section-title">Opsi jawaban</h4>
                <p class="hint">Pilih tepat satu jawaban benar.</p>
              </div>
              <div class="option-cards">
                <div v-for="(opt, i) in form.options" :key="i" class="option-card" :class="{ 'is-correct': opt.is_correct }">
                  <div class="option-card-top">
                    <input v-model="opt.option_key" placeholder="A" class="opt-key" maxlength="4" title="Kunci opsi" />
                    <label class="opt-correct">
                      <input type="radio" name="pg-correct" :checked="opt.is_correct" @change="setPgCorrect(i)" />
                      Jawaban benar
                    </label>
                    <button
                      v-if="form.options.length > 2"
                      type="button"
                      class="btn-remove-opt"
                      title="Hapus opsi"
                      @click="form.options.splice(i, 1)"
                    >×</button>
                  </div>
                  <RichTextEditor v-model="opt.body" :placeholder="'Isi opsi ' + (opt.option_key || (i + 1))" min-height="72px" />
                </div>
              </div>
              <button type="button" class="btn-small" @click="form.options.push({ option_key: nextOptionKey(), body: '', is_correct: false })">+ Tambah opsi</button>
            </section>

            <section v-if="form.type === 'pg_kompleks'" class="qform-section">
              <div class="qform-section-head">
                <h4 class="qform-section-title">Opsi jawaban</h4>
                <p class="hint">Centang semua opsi benar dan isi bobotnya. Saat ujian, satu pilihan salah membuat nilai soal ini 0. Opsi benar yang belum dipilih mengurangi nilai sesuai bobotnya.</p>
              </div>
              <div class="option-cards">
                <div v-for="(opt, i) in form.options" :key="i" class="option-card" :class="{ 'is-correct': opt.is_correct }">
                  <div class="option-card-top">
                    <input v-model="opt.option_key" placeholder="A" class="opt-key" maxlength="4" title="Kunci opsi" />
                    <label class="opt-correct">
                      <input v-model="opt.is_correct" type="checkbox" />
                      Benar
                    </label>
                    <div class="opt-weight-wrap">
                      <label class="opt-weight-label">Bobot</label>
                      <input v-model.number="opt.option_weight" type="number" min="0" step="0.01" class="opt-weight" />
                    </div>
                    <button
                      v-if="form.options.length > 2"
                      type="button"
                      class="btn-remove-opt"
                      title="Hapus opsi"
                      @click="form.options.splice(i, 1)"
                    >×</button>
                  </div>
                  <RichTextEditor v-model="opt.body" :placeholder="'Isi opsi ' + (opt.option_key || (i + 1))" min-height="72px" />
                </div>
              </div>
              <button type="button" class="btn-small" @click="form.options.push({ option_key: nextOptionKey(), body: '', is_correct: false, option_weight: 0 })">+ Tambah opsi</button>
            </section>

            <section v-if="form.type === 'matching'" class="qform-section matching-form">
              <h4 class="qform-section-title">Pasangan mencocokkan</h4>
              <p class="hint">Siswa yang memasangkan salah satu baris dengan jawaban yang salah mendapat 0 pada soal ini. Baris yang dikosongkan mengurangi nilai secara proporsional.</p>
              <div class="matching-columns">
                <div class="matching-col">
                  <strong>Kolom kiri</strong>
                  <div v-for="(item, i) in form.matching_left" :key="'L'+i" class="matching-item">
                    <span class="matching-num">{{ i + 1 }}.</span>
                    <input v-model="item.text" placeholder="Teks kiri" />
                  </div>
                  <button type="button" class="btn-small" @click="addMatchingLeft">+ Baris kiri</button>
                </div>
                <div class="matching-col">
                  <strong>Kolom kanan</strong>
                  <div v-for="(item, i) in form.matching_right" :key="'R'+i" class="matching-item">
                    <span class="matching-num">{{ i + 1 }}.</span>
                    <input v-model="item.text" placeholder="Teks kanan" />
                  </div>
                  <button type="button" class="btn-small" @click="form.matching_right.push({ id: '', text: '' })">+ Baris kanan</button>
                </div>
              </div>
              <div class="matching-correct">
                <strong>Kunci pasangan (kiri → kanan)</strong>
                <p class="hint">Untuk setiap item kiri, pilih pasangan kanan yang benar.</p>
                <div v-for="(leftItem, i) in form.matching_left" :key="'C'+i" class="correct-row">
                  <span class="correct-left">{{ i + 1 }}. {{ leftItem.text || '(kosong)' }}</span>
                  <select v-model="form.matching_correct[i]">
                    <option value="">— Pilih kanan —</option>
                    <option v-for="(r, j) in form.matching_right" :key="j" :value="String(j)">{{ j + 1 }}. {{ r.text || '(kosong)' }}</option>
                  </select>
                </div>
              </div>
            </section>

            <section v-if="form.type === 'isian'" class="qform-section">
              <h4 class="qform-section-title">Kunci jawaban</h4>
              <div class="form-group">
                <label>Jawaban utama *</label>
                <input v-model="form.key_answer" type="text" placeholder="Teks yang harus cocok" />
                <p class="hint">Perbandingan tanpa membedakan huruf besar/kecil. Kunci isian hanya teks polos; rumus lebih aman di pilihan ganda atau uraian.</p>
              </div>
              <div class="form-group">
                <label>Alias <span class="optional">(opsional)</span></label>
                <div v-for="(alias, ai) in form.key_answer_aliases" :key="'alias'+ai" class="alias-row">
                  <input v-model="form.key_answer_aliases[ai]" type="text" :placeholder="'Alias ' + (ai + 1)" />
                  <button type="button" class="btn-remove-opt" title="Hapus alias" @click="form.key_answer_aliases.splice(ai, 1)">×</button>
                </div>
                <button type="button" class="btn-small" @click="form.key_answer_aliases.push('')">+ Alias</button>
                <p class="hint">Contoh: kunci <code>H2O</code>, alias <code>h2o</code> / <code>air</code>.</p>
              </div>
            </section>
          </div>

          <footer class="qform-footer modal-actions">
            <button type="button" class="btn-secondary" @click="showForm = false">Batal</button>
            <button class="btn-primary" @click="submitQuestion" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan soal' }}</button>
          </footer>
        </div>
      </div>

      <!-- Quick-add stimulus -->
      <div v-if="showStimulusForm" class="modal-overlay modal-overlay-stimulus" @click.self="showStimulusForm = false">
        <div class="modal content-card modal-wide">
          <h3>Buat stimulus baru</h3>
          <p class="hint block">Stimulus hanya untuk bank ini. Setelah disimpan akan otomatis terpilih untuk soal ini, dan bisa dipilih lagi untuk soal lain. Bisa teks + gambar/audio/video/YouTube.</p>
          <div class="form-group">
            <label>Judul (untuk identifikasi di daftar)</label>
            <input v-model="stimulusForm.title" type="text" placeholder="Contoh: Bacaan tentang ekosistem / Video fotosintesis" maxlength="255" />
          </div>
          <StimulusMediaPanel v-model="stimulusForm.media" />
          <div class="form-group">
            <label>Konten teks</label>
            <RichTextEditor v-model="stimulusForm.content" placeholder="Teks, tabel, atau gambar tambahan (opsional jika sudah ada media)" min-height="180px" />
          </div>
          <div class="modal-actions">
            <button class="btn-primary" @click="submitStimulusForm" :disabled="savingStimulus">{{ savingStimulus ? 'Menyimpan...' : 'Simpan' }}</button>
            <button type="button" class="btn-secondary" @click="showStimulusForm = false">Batal</button>
          </div>
        </div>
      </div>

      <!-- Import Excel -->
      <div v-if="showImportModal" class="modal-overlay" @click.self="showImportModal = false">
        <div class="modal content-card">
          <h3>Import soal (Excel / CSV)</h3>
          <p class="hint block">
            Unduh template, isi baris soal (PG / isian / uraian / PG kompleks), lalu unggah.
            Kolom <code>stimulus_title</code> sama = stimulus bersama untuk beberapa soal.
          </p>
          <div class="modal-actions" style="margin-bottom: 1rem;">
            <button type="button" class="btn-secondary" :disabled="templateLoading" @click="downloadTemplate">
              {{ templateLoading ? 'Mengunduh…' : 'Unduh template' }}
            </button>
          </div>
          <div class="form-group">
            <label>File Excel / CSV</label>
            <input type="file" accept=".xlsx,.xls,.csv,.txt" @change="onImportFileChange" />
          </div>
          <div v-if="importResult" class="import-result">
            <p>Berhasil: {{ importResult.created }} · Gagal: {{ importResult.failed }}</p>
            <ul v-if="importResult.errors?.length">
              <li v-for="(err, i) in importResult.errors.slice(0, 8)" :key="i">{{ err }}</li>
            </ul>
          </div>
          <div class="modal-actions">
            <button class="btn-primary" :disabled="!importFile || importLoading" @click="submitImport">
              {{ importLoading ? 'Mengimpor…' : 'Import' }}
            </button>
            <button type="button" class="btn-secondary" @click="showImportModal = false">Tutup</button>
          </div>
        </div>
      </div>

      <!-- Preview seperti siswa -->
      <div v-if="showPreview" class="modal-overlay" @click.self="closePreview">
        <div class="modal content-card modal-wide preview-modal">
          <div class="preview-header">
            <h3>Preview soal</h3>
            <span class="preview-badge">
              {{ typeLabel(previewQuestion?.type) }}
              · bobot {{ previewQuestion?.weight }}
              <template v-if="previewQuestion?.cognitive_level"> · {{ cognitiveLevelLabel(previewQuestion.cognitive_level) }}</template>
            </span>
          </div>
          <p class="hint">Tampilan mirip yang dilihat siswa (tanpa kunci jawaban).</p>

          <div v-if="previewQuestion?.stimulus" class="preview-stimulus">
            <div class="preview-stimulus-label">Stimulus</div>
            <div v-if="previewQuestion.stimulus.title" class="preview-stimulus-title">{{ previewQuestion.stimulus.title }}</div>
            <StimulusMediaView :media="previewQuestion.stimulus.media || []" />
            <QuestionHtml class="preview-html" :html="previewQuestion.stimulus.content || ''" />
          </div>

          <div class="preview-body">
            <QuestionHtml class="preview-html" :html="previewQuestion?.body || ''" />
          </div>

          <div v-if="previewQuestion?.type === 'pg' || previewQuestion?.type === 'pg_kompleks'" class="preview-options">
            <label
              v-for="(opt, i) in (previewQuestion.options || [])"
              :key="opt.id || i"
              class="preview-option"
            >
              <input
                :type="previewQuestion.type === 'pg' ? 'radio' : 'checkbox'"
                disabled
                :name="'preview-opt'"
              />
              <span class="preview-opt-key">{{ opt.option_key || String.fromCharCode(65 + i) }}.</span>
              <QuestionHtml class="preview-html" tag="span" :html="opt.body || ''" />
            </label>
          </div>

          <div v-else-if="previewQuestion?.type === 'matching'" class="preview-matching">
            <div class="matching-columns">
              <div class="matching-col">
                <strong>Kolom kiri</strong>
                <div v-for="(item, i) in (previewQuestion.matching_data?.left || [])" :key="'pl'+i" class="matching-item">
                  {{ i + 1 }}. {{ item.text }}
                </div>
              </div>
              <div class="matching-col">
                <strong>Kolom kanan</strong>
                <div v-for="(item, i) in (previewQuestion.matching_data?.right || [])" :key="'pr'+i" class="matching-item">
                  {{ i + 1 }}. {{ item.text }}
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="previewQuestion?.type === 'isian'" class="preview-isian">
            <input type="text" disabled placeholder="Kotak isian jawaban siswa" />
          </div>

          <div v-else-if="previewQuestion?.type === 'uraian'" class="preview-isian">
            <textarea disabled rows="4" placeholder="Kotak uraian jawaban siswa"></textarea>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-primary" @click="openForm(previewQuestion); closePreview()">Edit soal</button>
            <button type="button" class="btn-secondary" @click="closePreview">Tutup</button>
          </div>
        </div>
      </div>
    </div>
</template>

<script setup>
import ListFilterBar from '@/components/ListFilterBar.vue'

import { ref, reactive, onMounted, watch, computed } from 'vue'
import { useRoute } from 'vue-router'
import TableAction from '@/components/TableAction.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import QuestionHtml from '@/components/QuestionHtml.vue'
import { collapseMathHtml } from '@/utils/questionMath'
import StimulusMediaPanel from '@/components/StimulusMediaPanel.vue'
import StimulusMediaView from '@/components/StimulusMediaView.vue'
import { examApi } from '@/api/exam'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const bankId = ref(route.params.bankId ? Number(route.params.bankId) : null)
const currentBank = ref(null)

const toast = useToast()
const questions = ref([])
const subjects = ref([])
const stimuli = ref([])
const loading = ref(false)
const filterSubjectId = ref('')
const filterSearch = ref('')
const filterType = ref('')
const filterStatus = ref('')
const filterTopic = ref('')
const topicOptions = ref([])
const filterCognitiveLevel = ref('')
const filterDifficulty = ref('')
const filterTag = ref('')
const filterStimulusId = ref('')
const selectedIds = ref([])
const bulkLoading = ref(false)
const difficultyOptions = [
  { value: 'mudah', label: 'Mudah' },
  { value: 'sedang', label: 'Sedang' },
  { value: 'sulit', label: 'Sulit' },
]
const pagination = ref(null)
const perPage = ref(50)
const cognitiveLevelOptions = [
  { value: 'c1', label: 'C1 — Mengingat' },
  { value: 'c2', label: 'C2 — Memahami' },
  { value: 'c3', label: 'C3 — Menerapkan' },
  { value: 'c4', label: 'C4 — Menganalisis' },
  { value: 'c5', label: 'C5 — Mengevaluasi' },
  { value: 'c6', label: 'C6 — Mencipta' },
]
const showForm = ref(false)
const showStimulusForm = ref(false)
const showPreview = ref(false)
const showImportModal = ref(false)
const previewQuestion = ref(null)
const editingId = ref(null)
const saving = ref(false)
const savingStimulus = ref(false)
const duplicatingId = ref(null)
const reorderLoading = ref(false)
const dragFromIndex = ref(null)
const dragOverIndex = ref(null)
const importFile = ref(null)
const importLoading = ref(false)
const importResult = ref(null)
const templateLoading = ref(false)

const form = reactive({
  subject_id: '',
  stimulus_id: '',
  type: 'pg',
  status: 'ready',
  topic: '',
  body: '',
  weight: 1,
  cognitive_level: '',
  difficulty: '',
  tagsText: '',
  key_answer: '',
  key_answer_aliases: [],
  options: [{ option_key: 'A', body: '', is_correct: false }, { option_key: 'B', body: '', is_correct: false }],
  matching_left: [{ id: '1', text: '' }, { id: '2', text: '' }],
  matching_right: [{ id: '3', text: '' }, { id: '4', text: '' }],
  matching_correct: []
})

const stimulusForm = reactive({ title: '', content: '', media: [] })

const filteredStimuli = computed(() => stimuli.value || [])

const hasActiveFilters = computed(() =>
  !!(filterSearch.value.trim() || filterType.value || filterStatus.value || filterTopic.value || filterCognitiveLevel.value || filterDifficulty.value || filterTag.value.trim() || filterStimulusId.value)
)

const pageAllSelected = computed(() =>
  questions.value.length > 0 && questions.value.every(q => selectedIds.value.includes(q.id))
)

const selectedStimulusLabel = computed(() => {
  const st = stimuli.value.find(s => String(s.id) === String(filterStimulusId.value))
  return st ? stimulusOptionLabel(st) : 'Stimulus terpilih'
})

async function fetchQuestions(page = 1) {
  if (!bankId.value && !filterSubjectId.value) {
    questions.value = []
    pagination.value = null
    return
  }
  loading.value = true
  try {
    const params = { per_page: perPage.value, page }
    if (bankId.value) params.bank_soal_id = bankId.value
    else if (filterSubjectId.value) params.subject_id = filterSubjectId.value
    if (filterSearch.value.trim()) params.search = filterSearch.value.trim()
    if (filterType.value) params.type = filterType.value
    if (filterStatus.value) params.status = filterStatus.value
    if (filterTopic.value) params.topic = filterTopic.value
    if (filterCognitiveLevel.value) params.cognitive_level = filterCognitiveLevel.value
    if (filterDifficulty.value) params.difficulty = filterDifficulty.value
    if (filterTag.value.trim()) params.tag = filterTag.value.trim()
    if (filterStimulusId.value && filterStimulusId.value !== 'none') {
      params.stimulus_id = filterStimulusId.value
    } else if (filterStimulusId.value === 'none') {
      params.stimulus_id = 'none'
    }
    const res = await examApi.listQuestions(params)
    let list = res.data?.data ?? res.data ?? []
    if (!Array.isArray(list)) list = []
    questions.value = list
    const meta = res.data?.meta
    pagination.value = meta
      ? {
          current_page: meta.current_page,
          last_page: meta.last_page,
          per_page: meta.per_page ?? perPage.value,
          total: meta.total ?? 0,
        }
      : null
  } finally {
    loading.value = false
  }
}

function changePerPage(n) {
  perPage.value = n
  fetchQuestions(1)
}

function clearFilters() {
  filterSearch.value = ''
  filterType.value = ''
  filterStatus.value = ''
  filterTopic.value = ''
  filterCognitiveLevel.value = ''
  filterDifficulty.value = ''
  filterTag.value = ''
  filterStimulusId.value = ''
  selectedIds.value = []
  fetchQuestions(1)
}

function toggleSelected(id, checked) {
  if (checked) {
    if (!selectedIds.value.includes(id)) selectedIds.value = [...selectedIds.value, id]
  } else {
    selectedIds.value = selectedIds.value.filter(item => item !== id)
  }
}

function togglePage(checked) {
  const pageIds = questions.value.map(q => q.id)
  if (checked) {
    selectedIds.value = [...new Set([...selectedIds.value, ...pageIds])]
  } else {
    selectedIds.value = selectedIds.value.filter(id => !pageIds.includes(id))
  }
}

async function bulkDelete() {
  if (!selectedIds.value.length) return
  if (!confirm(`Hapus ${selectedIds.value.length} soal? Yang sudah terpasang di ujian akan dilewati.`)) return
  bulkLoading.value = true
  try {
    const res = await examApi.bulkDeleteQuestions(selectedIds.value)
    toast.success('Selesai', res.data?.message || 'Soal dihapus.')
    selectedIds.value = []
    await fetchQuestions(pagination.value?.current_page || 1)
  } catch (e) {
    toast.error('Gagal menghapus', e.response?.data?.message || 'Coba lagi.')
  } finally {
    bulkLoading.value = false
  }
}

async function bulkDuplicate() {
  if (!selectedIds.value.length) return
  bulkLoading.value = true
  try {
    const res = await examApi.bulkDuplicateQuestions(selectedIds.value)
    toast.success('Selesai', res.data?.message || 'Soal disalin.')
    selectedIds.value = []
    await fetchQuestions(pagination.value?.current_page || 1)
  } catch (e) {
    toast.error('Gagal menyalin', e.response?.data?.message || 'Coba lagi.')
  } finally {
    bulkLoading.value = false
  }
}

function filterByStimulus(stimulusId) {
  filterStimulusId.value = stimulusId != null ? String(stimulusId) : ''
  fetchQuestions(1)
}

async function loadBank() {
  if (!bankId.value) return
  try {
    const res = await examApi.getBank(bankId.value)
    currentBank.value = res.data?.data ?? res.data
    await Promise.all([loadStimuli(), loadTopics()])
  } catch (_) {
    currentBank.value = null
  }
}

async function loadTopics() {
  if (!bankId.value) {
    topicOptions.value = []
    return
  }
  try {
    const res = await examApi.listQuestionTopics(bankId.value)
    const list = res.data?.data ?? res.data ?? []
    topicOptions.value = Array.isArray(list) ? list : []
  } catch (_) {
    topicOptions.value = []
  }
}

async function loadSubjects() {
  try {
    const res = await examApi.listSubjects({ per_page: 100 })
    subjects.value = res.data?.data ?? res.data ?? []
  } catch (_) {
    subjects.value = []
  }
}

async function loadStimuli() {
  if (!bankId.value) {
    stimuli.value = []
    return
  }
  try {
    const res = await examApi.listStimuli({ bank_soal_id: bankId.value, per_page: 100 })
    stimuli.value = res.data?.data ?? res.data ?? []
  } catch (_) {
    stimuli.value = []
  }
}

function stimulusOptionLabel(st) {
  const label = st.title ? st.title.trim() : stripHtml(st.content || '').slice(0, 60)
  const suffix = st.questions_count != null && st.questions_count > 0 ? ` (${st.questions_count} soal)` : ''
  return (label || '(tanpa judul)') + suffix
}

function typeLabel(type) {
  const map = { pg: 'PG', pg_kompleks: 'PG Kompleks', matching: 'Mencocokkan', isian: 'Isian', uraian: 'Uraian' }
  return map[type] || type
}

function cognitiveLevelLabel(level) {
  const found = cognitiveLevelOptions.find(o => o.value === level)
  return found ? found.label : (level ? String(level).toUpperCase() : '—')
}

function stripHtml(html) {
  if (!html) return ''
  const div = document.createElement('div')
  div.innerHTML = html
  return (div.textContent || div.innerText || '').trim()
}

function nextOptionKey() {
  const used = new Set(form.options.map(o => (o.option_key || '').toUpperCase()))
  for (let i = 0; i < 26; i++) {
    const k = String.fromCharCode(65 + i)
    if (!used.has(k)) return k
  }
  return String(form.options.length + 1)
}

function setPgCorrect(index) {
  form.options.forEach((o, i) => {
    o.is_correct = i === index
  })
}

function addMatchingLeft() {
  form.matching_left.push({ id: '', text: '' })
  form.matching_correct.push('')
}

function openForm(q = null, opts = {}) {
  editingId.value = q?.id ?? null
  form.subject_id = q?.subject_id ?? ''
  const prefer = opts.preferStimulus != null ? String(opts.preferStimulus) : ''
  form.stimulus_id = q?.stimulus_id != null
    ? String(q.stimulus_id)
    : (prefer || (filterStimulusId.value && filterStimulusId.value !== 'none' ? filterStimulusId.value : ''))
  form.type = q?.type ?? 'pg'
  form.status = q?.status === 'draft' ? 'draft' : 'ready'
  form.topic = q?.topic ?? ''
  form.body = q?.body ?? ''
  form.weight = q?.weight ?? 1
  form.cognitive_level = q?.cognitive_level ?? ''
  form.difficulty = q?.difficulty ?? ''
  form.tagsText = Array.isArray(q?.tags) ? q.tags.join(', ') : ''
  form.key_answer = q?.key_answer ?? ''
  form.key_answer_aliases = Array.isArray(q?.key_answer_aliases) ? [...q.key_answer_aliases] : []
  form.options = (q?.options && q.options.length) ? q.options.map(o => ({
    option_key: o.option_key,
    body: o.body,
    is_correct: !!o.is_correct,
    option_weight: o.option_weight != null ? Number(o.option_weight) : 0
  })) : (q?.type === 'pg_kompleks'
    ? [{ option_key: 'A', body: '', is_correct: false, option_weight: 0 }, { option_key: 'B', body: '', is_correct: false, option_weight: 0 }]
    : [{ option_key: 'A', body: '', is_correct: false }, { option_key: 'B', body: '', is_correct: false }])
  if (q?.type === 'matching' && q?.matching_data) {
    const md = q.matching_data
    form.matching_left = (md.left && md.left.length)
      ? md.left.map((x, i) => ({ id: x.id || String(i + 1), text: x.text || '' }))
      : [{ id: '1', text: '' }, { id: '2', text: '' }]
    form.matching_right = (md.right && md.right.length)
      ? md.right.map((x, i) => ({ id: x.id || String(form.matching_left.length + i + 1), text: x.text || '' }))
      : [{ id: '3', text: '' }, { id: '4', text: '' }]
    form.matching_correct = form.matching_left.map((l) => {
      const pair = (md.correct || []).find((c) => String(c.left_id) === String(l.id))
      if (!pair) return ''
      const rightIndex = form.matching_right.findIndex((r) => String(r.id) === String(pair.right_id))
      return rightIndex >= 0 ? String(rightIndex) : ''
    })
  } else {
    form.matching_left = [{ id: '1', text: '' }, { id: '2', text: '' }]
    form.matching_right = [{ id: '3', text: '' }, { id: '4', text: '' }]
    form.matching_correct = ['', '']
  }
  showForm.value = true
  loadStimuli()
}

function openPreview(q) {
  previewQuestion.value = q
  showPreview.value = true
}

function closePreview() {
  showPreview.value = false
  previewQuestion.value = null
}

function openStimulusForm() {
  stimulusForm.title = ''
  stimulusForm.content = ''
  stimulusForm.media = []
  showStimulusForm.value = true
}

async function submitStimulusForm() {
  const contentTrim = stripHtml(stimulusForm.content || '')
  if (!contentTrim && !(stimulusForm.media || []).length) {
    toast.error('Validasi', 'Isi konten teks atau tambahkan minimal satu media.')
    return
  }
  if (!bankId.value) {
    toast.error('Bank soal tidak diketahui', 'Pilih atau buat bank soal terlebih dahulu.')
    return
  }
  savingStimulus.value = true
  try {
    const res = await examApi.createStimulus({
      bank_soal_id: bankId.value,
      title: stimulusForm.title || null,
      content: collapseMathHtml(stimulusForm.content || ''),
      media: stimulusForm.media || []
    })
    const created = res.data?.data ?? res.data
    const newId = created?.id
    if (newId) {
      await loadStimuli()
      form.stimulus_id = String(newId)
    }
    showStimulusForm.value = false
    toast.success('Stimulus ditambahkan dan dipilih untuk soal ini.')
  } catch (e) {
    const msg = e?.formattedMessage || e?.response?.data?.message || 'Gagal menyimpan'
    toast.error('Gagal menyimpan stimulus', msg)
  } finally {
    savingStimulus.value = false
  }
}

function validateFormClient() {
  if (!stripHtml(form.body || '')) {
    return 'Pertanyaan wajib diisi.'
  }
  if (form.type === 'pg' || form.type === 'pg_kompleks') {
    const opts = form.options.filter(o => stripHtml(o.body || '') !== '')
    if (opts.length < 2) return 'Minimal 2 opsi jawaban.'
    const correct = opts.filter(o => o.is_correct)
    if (form.type === 'pg' && correct.length !== 1) return 'Pilihan ganda harus punya tepat satu jawaban benar.'
    if (form.type === 'pg_kompleks' && correct.length < 1) return 'Pilihan ganda kompleks minimal satu opsi benar.'
    if (form.type === 'pg_kompleks') {
      for (const o of correct) {
        if (!(Number(o.option_weight) > 0)) return 'Opsi benar harus punya bobot lebih dari 0.'
      }
    }
  }
  if (form.type === 'isian' && !(form.key_answer || '').trim() && !form.key_answer_aliases.some(a => (a || '').trim())) {
    return 'Kunci jawaban atau minimal satu alias wajib diisi untuk soal isian.'
  }
  if (form.type === 'matching') {
    const left = form.matching_left.filter(l => l.text.trim())
    const right = form.matching_right.filter(r => r.text.trim())
    if (left.length < 2 || right.length < 2) return 'Kolom kiri dan kanan minimal 2 baris.'
    const paired = form.matching_correct.filter((v, i) => form.matching_left[i]?.text.trim() && v !== '')
    if (paired.length < 1) return 'Minimal satu pasangan benar harus ditentukan.'
  }
  return null
}

async function submitQuestion() {
  const err = validateFormClient()
  if (err) {
    toast.error('Validasi', err)
    return
  }
  saving.value = true
  try {
    const payload = {
      stimulus_id: form.stimulus_id ? Number(form.stimulus_id) : null,
      type: form.type,
      status: form.status || 'ready',
      topic: form.topic.trim() || null,
      body: collapseMathHtml(form.body),
      weight: form.weight,
      cognitive_level: form.cognitive_level || null,
      difficulty: form.difficulty || null,
      tags: form.tagsText.split(',').map(tag => tag.trim()).filter(Boolean).slice(0, 8),
      key_answer: form.key_answer || null,
      key_answer_aliases: form.type === 'isian'
        ? form.key_answer_aliases.map(a => (a || '').trim()).filter(Boolean)
        : null
    }
    if (!bankId.value) payload.subject_id = form.subject_id
    if (form.type === 'pg' || form.type === 'pg_kompleks') {
      payload.options = form.options
        .filter(o => stripHtml(o.body || '') !== '')
        .map(o => ({
          option_key: o.option_key,
          body: collapseMathHtml(o.body),
          is_correct: !!o.is_correct,
          ...(form.type === 'pg_kompleks' && { option_weight: Number(o.option_weight) || 0 })
        }))
    }
    if (form.type === 'matching') {
      const leftSource = form.matching_left
        .map((l, origIdx) => ({ text: (l.text || '').trim(), origIdx }))
        .filter((l) => l.text)
      const rightSource = form.matching_right
        .map((r, origIdx) => ({ text: (r.text || '').trim(), origIdx }))
        .filter((r) => r.text)
      const left = leftSource.map((l, i) => ({ id: String(i + 1), text: l.text }))
      const right = rightSource.map((r, i) => ({ id: String(left.length + i + 1), text: r.text }))
      const rightOrigToFiltered = new Map(rightSource.map((r, i) => [r.origIdx, i]))
      const correct = []
      leftSource.forEach((l, li) => {
        const ri = form.matching_correct[l.origIdx]
        if (ri === undefined || ri === '' || ri === null) return
        const filteredRightIndex = rightOrigToFiltered.get(Number(ri))
        if (filteredRightIndex != null && right[filteredRightIndex]) {
          correct.push({ left_id: left[li].id, right_id: right[filteredRightIndex].id })
        }
      })
      payload.matching_data = { left, right, correct }
    }
    if (editingId.value) {
      await examApi.updateQuestion(editingId.value, payload)
      toast.success('Soal diperbarui.')
    } else {
      if (bankId.value) payload.bank_soal_id = bankId.value
      else payload.subject_id = form.subject_id
      await examApi.createQuestion(payload)
      toast.success('Soal ditambahkan.')
    }
    showForm.value = false
    await Promise.all([loadStimuli(), loadTopics()])
    fetchQuestions(pagination.value?.current_page || 1)
  } catch (e) {
    const errors = e.response?.data?.errors
    const first = errors && typeof errors === 'object' ? Object.values(errors).flat().find(Boolean) : null
    toast.error(
      'Gagal menyimpan soal',
      first || e.formattedMessage || e.response?.data?.message || 'Perubahan tidak dapat disimpan. Coba lagi.'
    )
  } finally {
    saving.value = false
  }
}

async function duplicateQuestion(q) {
  if (!q?.id) return
  duplicatingId.value = q.id
  try {
    await examApi.duplicateQuestion(q.id)
    toast.success('Soal diduplikat (stimulus tetap sama jika ada).')
    await loadStimuli()
    fetchQuestions(1)
  } catch (e) {
    toast.error('Gagal menduplikat soal', e.response?.data?.message || 'Coba lagi.')
  } finally {
    duplicatingId.value = null
  }
}

async function applyReorder(orderedIds) {
  if (!bankId.value || !orderedIds.length) return
  reorderLoading.value = true
  try {
    await examApi.reorderQuestions(bankId.value, orderedIds)
    toast.success('Urutan soal diperbarui.')
    await fetchQuestions(pagination.value?.current_page || 1)
  } catch (e) {
    toast.error('Gagal mengubah urutan', e.response?.data?.message || 'Coba lagi.')
  } finally {
    reorderLoading.value = false
  }
}

function moveQuestion(idx, delta) {
  const to = idx + delta
  if (to < 0 || to >= questions.value.length) return
  const ids = questions.value.map(q => q.id)
  const [moved] = ids.splice(idx, 1)
  ids.splice(to, 0, moved)
  applyReorder(ids)
}

function onDragStart(e, idx) {
  dragFromIndex.value = idx
  e.dataTransfer.effectAllowed = 'move'
  e.dataTransfer.setData('text/plain', String(idx))
}

function onDragOver(e, idx) {
  e.preventDefault()
  if (dragFromIndex.value === null) return
  if (idx !== dragFromIndex.value) dragOverIndex.value = idx
}

function onDrop(e, toIndex) {
  e.preventDefault()
  const from = dragFromIndex.value
  if (from === null || from === toIndex) {
    dragFromIndex.value = null
    dragOverIndex.value = null
    return
  }
  const ids = questions.value.map(q => q.id)
  const [moved] = ids.splice(from, 1)
  ids.splice(toIndex, 0, moved)
  dragFromIndex.value = null
  dragOverIndex.value = null
  applyReorder(ids)
}

function onDragEnd() {
  dragFromIndex.value = null
  dragOverIndex.value = null
}

function openImportModal() {
  importFile.value = null
  importResult.value = null
  showImportModal.value = true
}

function onImportFileChange(e) {
  importFile.value = e.target?.files?.[0] || null
}

async function downloadTemplate() {
  templateLoading.value = true
  try {
    const res = await examApi.downloadImportTemplate()
    const blob = new Blob([res.data], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'template-import-soal.xlsx'
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    toast.error('Gagal unduh template', e.response?.data?.message || 'Coba lagi.')
  } finally {
    templateLoading.value = false
  }
}

async function submitImport() {
  if (!bankId.value || !importFile.value) return
  importLoading.value = true
  importResult.value = null
  try {
    const res = await examApi.importQuestions(bankId.value, importFile.value)
    importResult.value = res.data?.data ?? res.data
    toast.success(res.data?.message || 'Import selesai.')
    await loadStimuli()
    fetchQuestions(1)
  } catch (e) {
    toast.error('Gagal import', e.response?.data?.message || 'Coba lagi.')
  } finally {
    importLoading.value = false
  }
}

async function confirmDelete(q) {
  const used = q.exams_count || 0
  if (used > 0) {
    toast.error('Tidak bisa dihapus', `Soal terpasang di ${used} ujian. Lepas dari paket ujian dulu, atau biarkan di bank.`)
    return
  }
  if (!confirm('Hapus soal ini?')) return
  try {
    await examApi.deleteQuestion(q.id)
    toast.success('Soal dihapus.')
    await loadStimuli()
    fetchQuestions(pagination.value?.current_page || 1)
  } catch (e) {
    toast.error('Gagal menghapus soal', e.response?.data?.message || 'Soal tidak dapat dihapus. Coba lagi.')
  }
}

onMounted(() => {
  loadSubjects()
  if (bankId.value) {
    loadBank()
    fetchQuestions(1)
  }
})

watch(() => route.params.bankId, (id) => {
  bankId.value = id ? Number(id) : null
  currentBank.value = null
  filterSearch.value = ''
  filterType.value = ''
  filterStatus.value = ''
  filterTopic.value = ''
  filterCognitiveLevel.value = ''
  filterDifficulty.value = ''
  filterTag.value = ''
  filterStimulusId.value = ''
  topicOptions.value = []
  selectedIds.value = []
  if (bankId.value) {
    loadBank()
    fetchQuestions(1)
  }
})

watch(() => form.type, (newType) => {
  if (newType === 'pg_kompleks') {
    form.options.forEach(o => {
      if (o.option_weight == null) o.option_weight = 0
    })
  }
})
</script>

<style scoped>
.bank-page { padding: 1rem; }
.page-header { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; margin-bottom: 1rem; }
.page-header h2 { margin: 0; flex-basis: 100%; }
.breadcrumb { font-size: 0.875rem; margin-bottom: 0.25rem; }
.breadcrumb a { color: #2563eb; text-decoration: none; }
.breadcrumb .sep { color: #9ca3af; margin: 0 0.25rem; }
.breadcrumb .current { color: #374151; }
.header-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.info-box { text-align: center; padding: 2rem; }
.info-box p { margin-bottom: 1rem; }
.form-group.info-row { margin-bottom: 0.5rem; color: #6b7280; }
.btn-primary { padding: 0.5rem 1rem; background: #059669; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
.btn-sm { padding: 0.35rem 0.75rem; font-size: 0.875rem; }
.content-card { background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1rem; }
.filters-bar { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.filter-search { flex: 1; min-width: 180px; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; }
.filter-select { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; min-width: 140px; }
.table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; max-width: 100%; }
.stimulus-banner {
  display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; justify-content: space-between;
  background: #f0fdf4; border: 1px solid #86efac;
}
.banner-hint { display: block; font-size: 0.8125rem; color: #64748b; margin-top: 0.15rem; }
.stimulus-chip {
  background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; border-radius: 999px;
  padding: 0.15rem 0.55rem; font-size: 0.8125rem; cursor: pointer; max-width: 220px;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.stimulus-chip:hover { background: #d1fae5; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
.row-actions { display: flex; flex-wrap: wrap; gap: 0.25rem; }
.btn-action { font-size: 0.8125rem; background: none; border: none; cursor: pointer; padding: 0.2rem 0.35rem; }
.btn-preview { color: #2563eb; }
.btn-edit { color: #059669; }
.btn-dup { color: #7c3aed; }
.btn-delete { color: #dc2626; }
.pagination { display: flex; gap: 0.75rem; align-items: center; justify-content: center; margin-top: 1rem; }
.pagination button { padding: 0.35rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; cursor: pointer; }
.pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: flex-start; justify-content: center; z-index: 1050; overflow-y: auto; padding: 1rem; }
.modal-overlay-stimulus { z-index: 1060; }
.modal { max-width: 560px; width: 100%; max-height: calc(100vh - 2rem); overflow-y: auto; }
.modal-wide { max-width: 720px; }
.modal-question-form {
  max-width: 820px;
  width: 100%;
  max-height: calc(100vh - 2rem);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  padding: 0;
  margin: 0.5rem 0 1.5rem;
}
.qform-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid #e5e7eb;
  flex-shrink: 0;
}
.qform-header h3 { margin: 0 0 0.2rem; font-size: 1.15rem; }
.qform-meta { margin: 0; font-size: 0.85rem; color: #6b7280; }
.btn-icon-close {
  border: none; background: #f3f4f6; color: #4b5563; width: 32px; height: 32px;
  border-radius: 6px; font-size: 1.25rem; line-height: 1; cursor: pointer; flex-shrink: 0;
}
.btn-icon-close:hover { background: #e5e7eb; }
.qform-body { padding: 1rem 1.25rem; overflow-y: auto; flex: 1; min-height: 0; }
.qform-section {
  margin-bottom: 1.25rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid #f1f5f9;
}
.qform-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.qform-section-title { margin: 0 0 0.75rem; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; }
.qform-section-head { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 0.35rem; margin-bottom: 0.75rem; }
.qform-section-head .qform-section-title { margin: 0; }
.qform-section-head .hint { margin: 0; }
.qform-grid { display: grid; grid-template-columns: 1fr 140px; gap: 0.75rem; }
.form-group { margin-bottom: 0.85rem; }
.form-group:last-child { margin-bottom: 0; }
.form-group-flush { margin-bottom: 0; }
.form-group label { display: block; margin-bottom: 0.3rem; font-size: 0.9rem; font-weight: 500; color: #374151; }
.form-group label .optional { font-weight: 400; color: #9ca3af; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.5rem 0.65rem; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; }
.field-hint { margin: 0.35rem 0 0; padding: 0.5rem 0.65rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px; }
.hint { margin: 0.25rem 0 0; font-size: 0.85rem; color: #6b7280; }
.hint.block { margin-bottom: 0.5rem; }
.stimulus-row { display: flex; gap: 0.5rem; align-items: center; }
.stimulus-row select { flex: 1; min-width: 0; }
.btn-add-stimulus { flex-shrink: 0; white-space: nowrap; }
.option-cards { display: flex; flex-direction: column; gap: 0.65rem; margin-bottom: 0.65rem; }
.option-card {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 0.65rem 0.75rem;
  background: #fafafa;
}
.option-card.is-correct {
  border-color: #6ee7b7;
  background: #f0fdf4;
}
.option-card :deep(.rich-text-editor) { width: 100%; }
.option-card :deep(.ql-toolbar.ql-snow) {
  flex-wrap: wrap;
  padding: 4px;
  background: #fff;
  border-radius: 6px 6px 0 0;
}
.option-card :deep(.ql-container.ql-snow) {
  border-radius: 0 0 6px 6px;
  background: #fff;
}
.option-card-top {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
}
.opt-key {
  width: 48px;
  flex-shrink: 0;
  padding: 0.4rem 0.35rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  text-align: center;
  font-weight: 700;
  background: #fff;
}
.opt-correct {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.875rem;
  color: #374151;
  cursor: pointer;
  white-space: nowrap;
  margin-right: auto;
}
.opt-weight-wrap { display: flex; align-items: center; gap: 0.35rem; }
.opt-weight-label { font-size: 0.85rem; color: #6b7280; white-space: nowrap; margin: 0; }
.opt-weight { width: 72px; padding: 0.35rem 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; }
.btn-remove-opt {
  border: none; background: #fee2e2; color: #b91c1c; border-radius: 4px;
  width: 28px; height: 28px; cursor: pointer; flex-shrink: 0; margin-left: auto;
}
.matching-form .matching-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
.matching-form .matching-col {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.75rem;
}
.matching-form .matching-col strong { display: block; margin-bottom: 0.5rem; font-size: 0.875rem; }
.matching-item { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.matching-item .matching-num { min-width: 1.5rem; color: #6b7280; font-size: 0.875rem; }
.matching-item input { flex: 1; min-width: 0; padding: 0.4rem 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; }
.matching-correct { margin-top: 0.25rem; padding: 0.75rem; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; }
.matching-correct strong { display: block; margin-bottom: 0.25rem; font-size: 0.875rem; }
.matching-correct .correct-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; align-items: center; margin-top: 0.5rem; }
.matching-correct .correct-left { font-size: 0.875rem; color: #374151; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.matching-correct .correct-row select { min-width: 0; width: 100%; padding: 0.4rem 0.5rem; }
.btn-small {
  margin-top: 0.15rem; padding: 0.4rem 0.75rem; font-size: 0.875rem; cursor: pointer;
  border: 1px dashed #94a3b8; background: #fff; color: #475569; border-radius: 6px;
}
.btn-small:hover { border-color: #059669; color: #059669; background: #ecfdf5; }
.qform-footer {
  margin: 0;
  padding: 0.85rem 1.25rem;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  flex-shrink: 0;
}
.modal-actions { margin-top: 1rem; display: flex; gap: 0.5rem; }
.btn-secondary { padding: 0.5rem 1rem; background: #e5e7eb; color: #374151; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
.btn-link-stimulus { text-decoration: none; }
.empty-state { text-align: center; padding: 2rem; }

.preview-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem; }
.preview-header h3 { margin: 0; }
.preview-badge { font-size: 0.75rem; background: #f1f5f9; color: #475569; padding: 0.2rem 0.5rem; border-radius: 999px; }
.preview-stimulus {
  margin: 1rem 0; padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
}
.preview-stimulus-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; margin-bottom: 0.35rem; }
.preview-stimulus-title { font-weight: 600; margin-bottom: 0.35rem; }
.preview-body { margin: 1rem 0; }
.preview-html :deep(img) { max-width: 100%; height: auto; }
.preview-options { display: flex; flex-direction: column; gap: 0.5rem; }
.preview-option {
  display: flex; gap: 0.5rem; align-items: flex-start; padding: 0.65rem 0.75rem;
  border: 1px solid #e5e7eb; border-radius: 8px; background: #fff;
}
.preview-opt-key { font-weight: 600; flex-shrink: 0; }
.preview-matching .matching-columns { display: flex; gap: 1.5rem; }
.preview-isian input,
.preview-isian textarea {
  width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 6px; background: #f9fafb;
}
.reorder-hint { font-size: 0.8125rem; color: #64748b; margin: 0 0 0.75rem; }
.used-chip {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 600;
  color: #0369a1;
  background: #e0f2fe;
  border-radius: 999px;
  padding: 0.15rem 0.5rem;
  white-space: nowrap;
}
.used-none { color: #94a3b8; }
.col-check { width: 2.25rem; text-align: center; }
.bulk-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.75rem;
  padding: 0.5rem 0.75rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}
.btn-danger {
  background: #dc2626;
  color: #fff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
.btn-sm { padding: 0.35rem 0.7rem; font-size: 0.85rem; }
.tag-row { display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.25rem; }
.tag-chip {
  font-size: 0.7rem;
  background: #f1f5f9;
  color: #334155;
  border-radius: 999px;
  padding: 0.05rem 0.4rem;
}
.filter-tag { max-width: 160px; }
.status-chip {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 999px;
  padding: 0.1rem 0.45rem;
}
.status-chip.ready { color: #166534; background: #dcfce7; }
.status-chip.draft { color: #92400e; background: #fef3c7; }
.level-chip {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 700;
  color: #5b21b6;
  background: #ede9fe;
  border-radius: 6px;
  padding: 0.12rem 0.4rem;
  letter-spacing: 0.02em;
}
.col-ord { width: 72px; vertical-align: middle; }
.drag-handle { cursor: grab; color: #94a3b8; margin-right: 0.25rem; user-select: none; }
.move-buttons { display: inline-flex; flex-direction: column; gap: 0.1rem; }
.btn-move {
  border: 1px solid #e2e8f0; background: #fff; border-radius: 4px; width: 22px; height: 18px;
  line-height: 1; font-size: 0.7rem; cursor: pointer; padding: 0;
}
.btn-move:disabled { opacity: 0.4; cursor: not-allowed; }
tr.drag-over { background: #ecfdf5; }
tr.dragging { opacity: 0.6; }
.alias-label { display: block; margin-top: 0.75rem; margin-bottom: 0.25rem; font-size: 0.875rem; }
.alias-row { display: flex; gap: 0.35rem; align-items: center; margin-bottom: 0.35rem; }
.alias-row input { flex: 1; }
.import-result { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.75rem; font-size: 0.875rem; }
.import-result ul { margin: 0.35rem 0 0; padding-left: 1.1rem; color: #b91c1c; }

@media (max-width: 768px) {
  .filter-search,
  .filter-select {
    min-width: 0;
    width: 100%;
  }
  .filters,
  .filters-bar {
    flex-direction: column;
    align-items: stretch;
  }
  .qform-grid {
    grid-template-columns: 1fr;
  }
  .stimulus-row,
  .alias-row,
  .option-card-top {
    flex-direction: column;
    align-items: stretch;
  }
  .opt-correct { margin-right: 0; }
  .btn-remove-opt { margin-left: 0; align-self: flex-end; }
  .stimulus-row select,
  .matching-correct .correct-row select {
    min-width: 0;
    width: 100%;
  }
  .matching-form .matching-columns,
  .matching-correct .correct-row {
    grid-template-columns: 1fr;
  }
  .qform-footer {
    flex-direction: column-reverse;
  }
  .qform-footer .btn-primary,
  .qform-footer .btn-secondary {
    width: 100%;
  }
}
</style>
