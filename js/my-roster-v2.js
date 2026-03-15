// my-roster-v2.js - Tabela + Time Titular
const api = async (path, options = {}) => {
  const doFetch = async (url) => {
    const res = await fetch(url, {
      headers: { 'Content-Type': 'application/json' },
      ...options,
    });
    let body = {};
    try { body = await res.json(); } catch { body = {}; }
    return { res, body };
  };

  let { res, body } = await doFetch(`/api/${path}`);
  if (res.status === 404) {
    ({ res, body } = await doFetch(`/public/api/${path}`));
  }
  if (!res.ok) throw body;
  return body;
};

function getOvrColor(ovr) {
  if (ovr >= 95) return '#00ff00';
  if (ovr >= 89) return '#00dd00';
  if (ovr >= 84) return '#ffff00';
  if (ovr >= 79) return '#ffd700';
  if (ovr >= 72) return '#ff9900';
  return '#ff4444';
}

function getPlayerPhotoUrl(player) {
  let customPhoto = (player.foto_adicional || '').toString().trim();
  if (customPhoto) {
    customPhoto = customPhoto.replace(/\\/g, '/');
    if (/^data:image\//i.test(customPhoto) || /^https?:\/\//i.test(customPhoto)) {
      return customPhoto;
    }
    return `/${customPhoto.replace(/^\/+/, '')}`;
  }
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(player.name)}&background=080931&color=ffffff&rounded=true&bold=true`;
}

function convertToBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(file);
  });
}

const starterPositionOrder = { GK: 0, DEF: 1, MID: 2, ATT: 3 };
const positionLabels = {
  GK: 'Goleiro',
  DEF: 'Defesa',
  MID: 'Meio',
  ATT: 'Ataque'
};
const lineupConfig = [
  { key: 'ATT', label: 'Ataque', slots: 3 },
  { key: 'MID', label: 'Meio', slots: 3 },
  { key: 'DEF', label: 'Defesa', slots: 4 },
  { key: 'GK', label: 'Goleiro', slots: 1 }
];

let allPlayers = [];
let currentSort = { field: 'position', ascending: true };
let currentSearch = '';
let editPhotoFile = null;

const DEFAULT_FA_LIMITS = { waiversUsed: 0, waiversMax: 3, signingsUsed: 0, signingsMax: 3 };
let currentFALimits = { ...DEFAULT_FA_LIMITS };

async function loadFreeAgencyLimits() {
  if (!window.__TEAM_ID__) return;
  try {
    const data = await api('free-agency.php?action=limits');
    currentFALimits = {
      waiversUsed: Number.isFinite(data.waivers_used) ? data.waivers_used : 0,
      waiversMax: Number.isFinite(data.waivers_max) && data.waivers_max > 0 ? data.waivers_max : DEFAULT_FA_LIMITS.waiversMax,
      signingsUsed: Number.isFinite(data.signings_used) ? data.signings_used : 0,
      signingsMax: Number.isFinite(data.signings_max) && data.signings_max > 0 ? data.signings_max : DEFAULT_FA_LIMITS.signingsMax,
    };
  } catch (err) {
    console.warn('Não foi possível carregar limites de FA:', err);
    currentFALimits = { ...DEFAULT_FA_LIMITS };
  }
  updateFreeAgencyCounters();
}

function updateFreeAgencyCounters() {
  const waiversEl = document.getElementById('waivers-count');
  const signingsEl = document.getElementById('signings-count');
  if (waiversEl) {
    waiversEl.textContent = `${currentFALimits.waiversUsed} / ${currentFALimits.waiversMax}`;
    waiversEl.classList.toggle('text-danger', currentFALimits.waiversMax && currentFALimits.waiversUsed >= currentFALimits.waiversMax);
  }
  if (signingsEl) {
    signingsEl.textContent = `${currentFALimits.signingsUsed} / ${currentFALimits.signingsMax}`;
    signingsEl.classList.toggle('text-danger', currentFALimits.signingsMax && currentFALimits.signingsUsed >= currentFALimits.signingsMax);
  }
}

function applyFilters(players) {
  const term = currentSearch.trim().toLowerCase();
  return players.filter(p => {
    if (!term) return true;
    const hay = `${p.name} ${p.position}`.toLowerCase();
    return hay.includes(term);
  });
}

function sortPlayers(field) {
  if (currentSort.field === field) {
    currentSort.ascending = !currentSort.ascending;
  } else {
    currentSort.field = field;
    currentSort.ascending = true;
  }
  renderPlayers(allPlayers);
}

function renderPlayers(players) {
  let sorted = applyFilters([...players]);
  sorted.sort((a, b) => {
    let aVal = a[currentSort.field];
    let bVal = b[currentSort.field];

    if (currentSort.field === 'trade') {
      aVal = a.available_for_trade ? 1 : 0;
      bVal = b.available_for_trade ? 1 : 0;
    }
    if (['ovr', 'age', 'seasons_in_league'].includes(currentSort.field)) {
      aVal = Number(aVal);
      bVal = Number(bVal);
    }

    if (aVal < bVal) return currentSort.ascending ? -1 : 1;
    if (aVal > bVal) return currentSort.ascending ? 1 : -1;

    // Em caso de empate por função, ordenar por posição base do futebol
    return 0;
  });

  renderBenchList(allPlayers);
  renderLineupField(allPlayers);

  renderPlayersMobileCards(sorted);

  const statusEl = document.getElementById('players-status');
  if (statusEl) {
    statusEl.style.display = 'none';
  }

  updateRosterStats();
  try {
    renderPlayersTable(sorted);
  } catch (e) {
    console.warn('Falha ao renderizar tabela:', e);
  }
}

function renderLineupField(players) {
  const field = document.getElementById('lineup-field');
  if (!field) return;
  field.innerHTML = '';

  const playersById = new Map(allPlayers.map(p => [String(p.id), p]));
  const lineupState = loadLineupState();
  const usedIds = new Set();

  const byPosition = { GK: [], DEF: [], MID: [], ATT: [] };
  (players || []).forEach((player) => {
    if (byPosition[player.position]) {
      byPosition[player.position].push(player);
    }
  });

  Object.keys(byPosition).forEach((key) => {
    byPosition[key].sort((a, b) => Number(b.ovr) - Number(a.ovr));
  });

  lineupConfig.forEach((row) => {
    const rowEl = document.createElement('div');
    rowEl.className = 'lineup-row';
    for (let i = 0; i < row.slots; i += 1) {
      const slotKey = String(i);
      const pickedId = lineupState[row.key]?.[slotKey] ?? null;
      let player = pickedId ? playersById.get(String(pickedId)) : null;
      if (!player) {
        const list = (byPosition[row.key] || []).filter(p => !usedIds.has(String(p.id)));
        player = list[0] || null;
      }
      if (player) {
        usedIds.add(String(player.id));
      }
      const slot = document.createElement('div');
      slot.className = 'lineup-slot';
      if (player) {
        slot.innerHTML = `
          <button class="lineup-btn" type="button" data-pos="${row.key}" data-slot="${i}">
            <div class="lineup-player">
              <div class="name">${player.name}</div>
              <div class="meta">${positionLabels[player.position] || player.position} · OVR ${player.ovr}</div>
            </div>
          </button>`;
      } else {
        slot.innerHTML = `
          <button class="lineup-btn" type="button" data-pos="${row.key}" data-slot="${i}">
            <div class="lineup-player lineup-placeholder">
              <div class="name">${row.label}</div>
              <div class="meta">Sem titular</div>
            </div>
          </button>`;
      }
      rowEl.appendChild(slot);
    }
    field.appendChild(rowEl);
  });

  field.querySelectorAll('.lineup-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      openLineupPicker(btn.dataset.pos, parseInt(btn.dataset.slot, 10));
    });
  });
}

function getLineupSelectedIds() {
  const state = loadLineupState();
  const selected = new Set();
  Object.values(state || {}).forEach(group => {
    if (!group) return;
    Object.values(group).forEach(id => {
      if (id) selected.add(String(id));
    });
  });
  return selected;
}

function renderBenchList(players) {
  const listEl = document.getElementById('bench-list');
  const emptyEl = document.getElementById('bench-empty');
  if (!listEl || !emptyEl) return;
  listEl.innerHTML = '';
  const selected = getLineupSelectedIds();
  const bench = players
    .filter(p => !selected.has(String(p.id)))
    .sort((a, b) => Number(b.ovr) - Number(a.ovr))
    .slice(0, 5);
  if (bench.length === 0) {
    emptyEl.style.display = 'block';
    return;
  }
  emptyEl.style.display = 'none';
  bench.forEach(p => {
    const item = document.createElement('div');
    item.className = 'list-group-item bg-transparent text-white d-flex justify-content-between align-items-center px-0';
    item.innerHTML = `
      <span>${p.name} <small class="text-light-gray">(${p.position})</small></span>
      <span class="fw-bold" style="color:${getOvrColor(p.ovr)}">${p.ovr}</span>`;
    listEl.appendChild(item);
  });
}

function getLineupStorageKey() {
  return `fut_lineup_${window.__TEAM_ID__ || 'guest'}`;
}

function loadLineupState() {
  try {
    const raw = localStorage.getItem(getLineupStorageKey());
    if (!raw) return {};
    const parsed = JSON.parse(raw);
    return parsed && typeof parsed === 'object' ? parsed : {};
  } catch {
    return {};
  }
}

function saveLineupState(state) {
  try {
    localStorage.setItem(getLineupStorageKey(), JSON.stringify(state));
  } catch {
    // ignore storage errors
  }
}

function openLineupPicker(positionKey, slotIndex) {
  const modalEl = document.getElementById('lineupPickerModal');
  const selectEl = document.getElementById('lineup-player-select');
  if (!modalEl || !selectEl) return;
  const pos = positionKey;
  const lineupState = loadLineupState();
  const currentId = lineupState[pos]?.[String(slotIndex)] ?? '';
  const usedIds = new Set();
  Object.values(lineupState).forEach(arr => {
    if (!arr) return;
    Object.values(arr).forEach(id => {
      if (id) usedIds.add(String(id));
    });
  });

  selectEl.innerHTML = '';
  const emptyOption = document.createElement('option');
  emptyOption.value = '';
  emptyOption.textContent = 'Sem jogador';
  selectEl.appendChild(emptyOption);

  allPlayers
    .filter(p => p.position === pos)
    .sort((a, b) => Number(b.ovr) - Number(a.ovr))
    .forEach(player => {
      const idStr = String(player.id);
      const option = document.createElement('option');
      option.value = idStr;
      option.textContent = `${player.name} • OVR ${player.ovr}`;
      if (idStr === String(currentId)) {
        option.selected = true;
      }
      if (usedIds.has(idStr) && idStr !== String(currentId)) {
        option.disabled = true;
      }
      selectEl.appendChild(option);
    });

  document.getElementById('lineup-position-key').value = pos;
  document.getElementById('lineup-slot-index').value = String(slotIndex);
  new bootstrap.Modal(modalEl).show();
}

function renderPlayersMobileCards(players) {
  const container = document.getElementById('players-mobile-cards');
  if (!container) return;
  container.innerHTML = '';
  container.style.display = '';
  if (!players || players.length === 0) {
    container.innerHTML = '<div class="text-center text-light-gray">Nenhum jogador encontrado.</div>';
    return;
  }

  players.forEach(p => {
    const canRetire = Number(p.age) >= 35;
    const photoUrl = getPlayerPhotoUrl(p);
    const card = document.createElement('div');
    card.className = 'roster-mobile-card';
    card.innerHTML = `
      <div class="d-flex justify-content-between align-items-start gap-2">
        <div class="d-flex align-items-center gap-2">
          <img src="${photoUrl}" alt="${p.name}"
               style="width: 44px; height: 44px; object-fit: cover; border-radius: 50%; border: 1px solid var(--fba-orange); background: #1a1a1a;"
               onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=080931&color=ffffff&rounded=true&bold=true'">
          <div>
            <div class="text-white fw-bold">${p.name}</div>
            <div class="text-light-gray small">${p.position}</div>
          </div>
        </div>
        <div class="text-end">
          <div class="fw-bold" style="color:${getOvrColor(p.ovr)}; font-size: 1.2rem;">${p.ovr}</div>
          <small class="text-light-gray">${p.age} anos</small>
        </div>
      </div>
      <div class="mt-2">
        ${p.available_for_trade ? '<span class="badge bg-success">Disponível</span>' : '<span class="badge bg-secondary">Indisp.</span>'}
      </div>
      <div class="roster-mobile-actions mt-3">
        <button class="btn btn-outline-light btn-sm btn-edit-player" data-id="${p.id}" title="Editar"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-outline-warning btn-sm btn-waive-player" data-id="${p.id}" data-name="${p.name}" title="Dispensar"><i class="bi bi-hand-thumbs-down"></i></button>
        ${canRetire ? `<button class="btn btn-outline-danger btn-sm btn-retire-player" data-id="${p.id}" data-name="${p.name}" title="Aposentar"><i class="bi bi-box-arrow-right"></i></button>` : ''}
        <button class="btn btn-sm ${p.available_for_trade ? 'btn-outline-success' : 'btn-outline-danger'} btn-toggle-trade" data-id="${p.id}" data-trade="${p.available_for_trade}" title="Disponibilidade para Troca">
          <i class="bi ${p.available_for_trade ? 'bi-check-circle' : 'bi-x-circle'}"></i>
        </button>
      </div>
    `;
    container.appendChild(card);
  });
}

function renderPlayersTable(players) {
  const wrapper = document.getElementById('players-table-wrapper');
  const tbody = document.getElementById('players-table-body');
  if (!wrapper || !tbody) return;
  tbody.innerHTML = '';
  if (!players || players.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-light-gray">Nenhum jogador encontrado.</td></tr>';
    wrapper.style.display = '';
    return;
  }
  players.forEach(p => {
    const canRetire = Number(p.age) >= 35;
    const photoUrl = getPlayerPhotoUrl(p);
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>
        <div class="d-flex align-items-center gap-2">
          <img src="${photoUrl}" alt="${p.name}"
               style="width: 36px; height: 36px; object-fit: cover; border-radius: 50%; border: 1px solid var(--fba-orange); background: #1a1a1a;"
               onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(p.name)}&background=080931&color=ffffff&rounded=true&bold=true'">
          <div class="d-flex flex-column">
            <span class="fw-semibold">${p.name}</span>
            <small class="text-light-gray">${p.position}</small>
          </div>
        </div>
      </td>
      <td>${p.position}</td>
      <td><span style="color:${getOvrColor(p.ovr)};" class="fw-bold">${p.ovr}</span></td>
      <td>${p.age}</td>
      <td>
        ${p.available_for_trade ? '<span class="badge bg-success">Disponível</span>' : '<span class="badge bg-secondary">Indisp.</span>'}
      </td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-light btn-edit-player" data-id="${p.id}" title="Editar"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-sm btn-outline-warning btn-waive-player" data-id="${p.id}" data-name="${p.name}" title="Dispensar"><i class="bi bi-hand-thumbs-down"></i></button>
        ${canRetire ? `<button class="btn btn-sm btn-outline-danger btn-retire-player" data-id="${p.id}" data-name="${p.name}" title="Aposentar"><i class="bi bi-box-arrow-right"></i></button>` : ''}
        <button class="btn btn-sm ${p.available_for_trade ? 'btn-outline-success' : 'btn-outline-danger'} btn-toggle-trade" data-id="${p.id}" data-trade="${p.available_for_trade}" title="Disponibilidade para Troca">
          <i class="bi ${p.available_for_trade ? 'bi-check-circle' : 'bi-x-circle'}"></i>
        </button>
      </td>`;
    tbody.appendChild(tr);
  });
  wrapper.style.display = '';
}

function updateRosterStats() {
  const totalPlayers = allPlayers.length;
  const topEight = [...allPlayers]
    .sort((a, b) => Number(b.ovr) - Number(a.ovr))
    .slice(0, 8)
    .reduce((sum, p) => sum + Number(p.ovr), 0);
  document.getElementById('total-players').textContent = `${totalPlayers} / 16`;
  document.getElementById('cap-top8').textContent = topEight;
}

async function loadPlayers() {
  const teamId = window.__TEAM_ID__;
  const statusEl = document.getElementById('players-status');
  const mobileCardsEl = document.getElementById('players-mobile-cards');
  if (!teamId) {
    if (statusEl) {
      statusEl.innerHTML = '<div class="alert alert-warning text-center"><i class="bi bi-exclamation-triangle me-2"></i>Você ainda não possui um time.</div>';
      statusEl.style.display = 'block';
    }
    if (mobileCardsEl) mobileCardsEl.style.display = 'none';
    return;
  }
  if (statusEl) {
    statusEl.innerHTML = '<div class="spinner-border text-orange" role="status"></div><p class="text-light-gray mt-2">Carregando jogadores...</p>';
    statusEl.style.display = 'block';
  }
  if (mobileCardsEl) mobileCardsEl.style.display = 'none';
  try {
    let data = null;
    try {
      data = await api(`team-players.php?team_id=${teamId}`);
    } catch (err) {
      data = null;
    }
    if (!data || data.success === false || !Array.isArray(data.players)) {
      data = await api(`players.php?team_id=${teamId}`);
    }
    allPlayers = Array.isArray(data.players) ? data.players.map((player) => ({
      ...player,
      ovr: player.ovr ?? player.overall ?? 0
    })) : [];
    currentSort = { field: 'position', ascending: true };
    renderPlayers(allPlayers);
    if (statusEl) statusEl.style.display = 'none';
  } catch (err) {
    console.error('Erro ao carregar:', err);
    if (statusEl) {
      statusEl.innerHTML = `<div class="alert alert-danger text-center"><i class="bi bi-x-circle me-2"></i>Erro ao carregar jogadores: ${err.error || 'Desconhecido'}</div>`;
      statusEl.style.display = 'block';
    }
  }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
  loadPlayers();
  loadFreeAgencyLimits();

  document.getElementById('btn-refresh-players')?.addEventListener('click', loadPlayers);
  document.getElementById('sort-select')?.addEventListener('change', (e) => sortPlayers(e.target.value));
  document.getElementById('players-search')?.addEventListener('input', (e) => {
    currentSearch = (e.target.value || '').toLowerCase();
    renderPlayers(allPlayers);
  });
  document.querySelector('#players-table thead')?.addEventListener('click', (e) => {
    const th = e.target.closest('th.sortable');
    if (th && th.dataset.sort) sortPlayers(th.dataset.sort);
  });

  document.getElementById('lineup-apply-btn')?.addEventListener('click', () => {
    const pos = document.getElementById('lineup-position-key').value;
    const slotIndex = document.getElementById('lineup-slot-index').value;
    const selectEl = document.getElementById('lineup-player-select');
    if (!pos || slotIndex === '' || !selectEl) return;
    const state = loadLineupState();
    if (!state[pos]) state[pos] = {};
    state[pos][String(slotIndex)] = selectEl.value || null;
    saveLineupState(state);
    bootstrap.Modal.getInstance(document.getElementById('lineupPickerModal')).hide();
    renderLineupField(allPlayers);
    renderBenchList(allPlayers);
  });

  document.getElementById('lineup-clear-btn')?.addEventListener('click', () => {
    const pos = document.getElementById('lineup-position-key').value;
    const slotIndex = document.getElementById('lineup-slot-index').value;
    if (!pos || slotIndex === '') return;
    const state = loadLineupState();
    if (state[pos]) {
      delete state[pos][String(slotIndex)];
    }
    saveLineupState(state);
    bootstrap.Modal.getInstance(document.getElementById('lineupPickerModal')).hide();
    renderLineupField(allPlayers);
    renderBenchList(allPlayers);
  });

  const editPhotoInput = document.getElementById('edit-foto-adicional');
  editPhotoInput?.addEventListener('change', (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    editPhotoFile = file;
    const preview = document.getElementById('edit-foto-preview');
    if (!preview) return;
    if (preview.dataset.objectUrl) {
      URL.revokeObjectURL(preview.dataset.objectUrl);
      delete preview.dataset.objectUrl;
    }
    if (window.URL && URL.createObjectURL) {
      const objectUrl = URL.createObjectURL(file);
      preview.src = objectUrl;
      preview.dataset.objectUrl = objectUrl;
      return;
    }
    const reader = new FileReader();
    reader.onload = (ev) => {
      preview.src = ev.target.result;
    };
    reader.readAsDataURL(file);
  });

  const formPlayer = document.getElementById('form-player');
  const handleAddPlayer = async () => {
    const form = formPlayer;
    if (!form) return;
    const teamId = window.__TEAM_ID__;
    if (!teamId) {
      alert('Você ainda não possui um time.');
      return;
    }
    const formData = new FormData(form);
    const payload = {
      team_id: teamId,
      name: (formData.get('name') || '').toString().trim(),
      age: parseInt(formData.get('age') || '0', 10),
      position: (formData.get('position') || '').toString().trim(),
      ovr: parseInt(formData.get('ovr') || '0', 10),
      available_for_trade: formData.get('available_for_trade') ? 1 : 0
    };

    if (!payload.name || !payload.age || !payload.position || !payload.ovr) {
      alert('Preencha nome, idade, posição e OVR.');
      return;
    }

    const btn = document.getElementById('btn-add-player');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Enviando...';
    }

    try {
      const res = await api('players.php', { method: 'POST', body: JSON.stringify(payload) });
      alert(res.message || 'Jogador adicionado.');
      form.reset();
      document.getElementById('available_for_trade').checked = true;
      loadPlayers();
    } catch (err) {
      alert('Erro ao cadastrar jogador: ' + (err.error || err.message || 'Desconhecido'));
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Cadastrar Jogador';
      }
    }
  };

  formPlayer?.addEventListener('submit', async (e) => {
    e.preventDefault();
    handleAddPlayer();
  });

  // Delegação para ações da tabela
  document.getElementById('players-table-body')?.addEventListener('click', async (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;
    if (btn.classList.contains('btn-toggle-trade')) {
      const playerId = btn.dataset.id;
      const currentStatus = (() => {
        const raw = String(btn.dataset.trade || '').toLowerCase();
        return raw === 'true' || raw === '1' || raw === 'yes';
      })();
      const newStatus = currentStatus ? 0 : 1;
      try {
        await api('players.php', { method: 'PUT', body: JSON.stringify({ id: playerId, available_for_trade: newStatus }) });
        loadPlayers();
      } catch (err) {
        alert('Erro ao atualizar: ' + (err.error || 'Desconhecido'));
      }
      return;
    }
    if (btn.classList.contains('btn-edit-player')) {
      const playerId = btn.dataset.id;
      const player = allPlayers.find(p => p.id == playerId);
      if (player) {
        document.getElementById('edit-player-id').value = player.id;
        document.getElementById('edit-name').value = player.name;
        editPhotoFile = null;
        const editPhotoField = document.getElementById('edit-foto-adicional');
        if (editPhotoField) editPhotoField.value = '';
        const editPreview = document.getElementById('edit-foto-preview');
        if (editPreview) editPreview.src = getPlayerPhotoUrl(player);
        document.getElementById('edit-age').value = player.age;
        document.getElementById('edit-position').value = player.position;
        document.getElementById('edit-ovr').value = player.ovr;
        document.getElementById('edit-available').checked = !!player.available_for_trade;
        new bootstrap.Modal(document.getElementById('editPlayerModal')).show();
      }
      return;
    }
    if (btn.classList.contains('btn-waive-player')) {
      const playerId = btn.dataset.id;
      const playerName = btn.dataset.name;
      if (confirm(`Dispensar ${playerName}?`)) {
        try {
          const res = await api('players.php', { method: 'DELETE', body: JSON.stringify({ id: playerId }) });
          alert(res.message || 'Jogador dispensado e enviado para a Free Agency!');
          loadPlayers();
          loadFreeAgencyLimits();
        } catch (err) {
          alert('Erro: ' + (err.error || 'Desconhecido'));
        }
      }
      return;
    }
    if (btn.classList.contains('btn-retire-player')) {
      const playerId = btn.dataset.id;
      const playerName = btn.dataset.name;
      if (confirm(`Aposentar ${playerName}?`)) {
        try {
          const res = await api('players.php', { method: 'DELETE', body: JSON.stringify({ id: playerId, retirement: true }) });
          alert(res.message || 'Jogador aposentado!');
          loadPlayers();
        } catch (err) {
          alert('Erro: ' + (err.error || 'Desconhecido'));
        }
      }
    }
  });

  // Delegação para ações nos cards mobile
  document.getElementById('players-mobile-cards')?.addEventListener('click', async (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;
    if (btn.classList.contains('btn-toggle-trade')) {
      const playerId = btn.dataset.id;
      const currentStatus = (() => {
        const raw = String(btn.dataset.trade || '').toLowerCase();
        return raw === 'true' || raw === '1' || raw === 'yes';
      })();
      const newStatus = currentStatus ? 0 : 1;
      try {
        await api('players.php', { method: 'PUT', body: JSON.stringify({ id: playerId, available_for_trade: newStatus }) });
        loadPlayers();
      } catch (err) {
        alert('Erro ao atualizar: ' + (err.error || 'Desconhecido'));
      }
      return;
    }
    if (btn.classList.contains('btn-edit-player')) {
      const playerId = btn.dataset.id;
      const player = allPlayers.find(p => p.id == playerId);
      if (player) {
        document.getElementById('edit-player-id').value = player.id;
        document.getElementById('edit-name').value = player.name;
        editPhotoFile = null;
        const editPhotoField = document.getElementById('edit-foto-adicional');
        if (editPhotoField) editPhotoField.value = '';
        const editPreview = document.getElementById('edit-foto-preview');
        if (editPreview) editPreview.src = getPlayerPhotoUrl(player);
        document.getElementById('edit-age').value = player.age;
        document.getElementById('edit-position').value = player.position;
        document.getElementById('edit-ovr').value = player.ovr;
        document.getElementById('edit-available').checked = !!player.available_for_trade;
        new bootstrap.Modal(document.getElementById('editPlayerModal')).show();
      }
      return;
    }
    if (btn.classList.contains('btn-waive-player')) {
      const playerId = btn.dataset.id;
      const playerName = btn.dataset.name;
      if (confirm(`Dispensar ${playerName}?`)) {
        try {
          const res = await api('players.php', { method: 'DELETE', body: JSON.stringify({ id: playerId }) });
          alert(res.message || 'Jogador dispensado e enviado para a Free Agency!');
          loadPlayers();
          loadFreeAgencyLimits();
        } catch (err) {
          alert('Erro: ' + (err.error || 'Desconhecido'));
        }
      }
      return;
    }
    if (btn.classList.contains('btn-retire-player')) {
      const playerId = btn.dataset.id;
      const playerName = btn.dataset.name;
      if (confirm(`Aposentar ${playerName}?`)) {
        try {
          const res = await api('players.php', { method: 'DELETE', body: JSON.stringify({ id: playerId, retirement: true }) });
          alert(res.message || 'Jogador aposentado!');
          loadPlayers();
        } catch (err) {
          alert('Erro: ' + (err.error || 'Desconhecido'));
        }
      }
    }
  });

  // Salvar edição
  document.getElementById('btn-save-edit')?.addEventListener('click', async () => {
    const data = {
      id: document.getElementById('edit-player-id').value,
      name: document.getElementById('edit-name').value,
      age: document.getElementById('edit-age').value,
      position: document.getElementById('edit-position').value,
      ovr: document.getElementById('edit-ovr').value,
      available_for_trade: document.getElementById('edit-available').checked ? 1 : 0
    };
    if (editPhotoFile) {
      data.foto_adicional = await convertToBase64(editPhotoFile);
    }
    try {
      await api('players.php', { method: 'PUT', body: JSON.stringify(data) });
      bootstrap.Modal.getInstance(document.getElementById('editPlayerModal')).hide();
      loadPlayers();
    } catch (err) {
      alert('Erro ao salvar: ' + (err.error || 'Desconhecido'));
    }
  });
});
