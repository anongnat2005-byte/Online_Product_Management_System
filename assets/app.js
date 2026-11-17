// หน้าเว็บจัดการสินค้า เรียกใช้ REST API ครบทั้ง 5 methods
const API = 'api/products.php';
const DJ = 'api/dummyjson.php';

const $ = (id) => document.getElementById(id);
const state = { q: '', category: '', sort: 'id', order: 'asc', page: 1, limit: 10 };
let pendingDeleteId = null;
let toastTimer = null;

// ---------- ตัวช่วย ----------
function esc(value) {
  return String(value ?? '').replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]));
}

function money(n) {
  return Number(n).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function toast(message, type = '') {
  const el = $('toast');
  el.textContent = message;
  el.className = 'toast ' + type;
  el.hidden = false;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { el.hidden = true; }, 3500);
}

// เรียก API แล้วคืน { status, data } ถ้าไม่สำเร็จจะ throw Error พร้อมข้อความจากเซิร์ฟเวอร์
async function request(url, options = {}) {
  const init = { method: 'GET', ...options };
  if (init.body && typeof init.body !== 'string') {
    init.body = JSON.stringify(init.body);
    init.headers = { 'Content-Type': 'application/json', ...(init.headers || {}) };
  }
  let res;
  try {
    res = await fetch(url, init);
  } catch (e) {
    throw new Error('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ (เปิด Apache ใน XAMPP หรือยัง?)');
  }
  let data = null;
  try { data = await res.json(); } catch (e) { /* ไม่ใช่ JSON */ }
  if (!res.ok) {
    const details = data && data.details ? ': ' + data.details.join(', ') : '';
    const err = new Error(((data && data.error) || 'เกิดข้อผิดพลาด') + details);
    err.status = res.status;
    throw err;
  }
  return { status: res.status, data };
}

// ---------- GET: โหลดรายการ ----------
async function loadCategories() {
  try {
    const { data } = await request(`${API}?categories=1`);
    const current = $('categoryFilter').value;
    $('categoryFilter').innerHTML = '<option value="">ทุกหมวดหมู่</option>' +
      data.data.map((c) => `<option value="${esc(c)}">${esc(c)}</option>`).join('');
    $('categoryFilter').value = data.data.includes(current) ? current : '';
    $('categoryList').innerHTML = data.data.map((c) => `<option value="${esc(c)}">`).join('');
  } catch (e) { /* โหลดหมวดหมู่ไม่ได้ก็ไม่เป็นไร */ }
}

async function loadProducts() {
  const params = new URLSearchParams({
    page: state.page, limit: state.limit, sort: state.sort, order: state.order,
  });
  if (state.q) params.set('q', state.q);
  if (state.category) params.set('category', state.category);

  try {
    const { data } = await request(`${API}?${params}`);
    // ถ้าลบจนหน้าปัจจุบันว่าง ให้ถอยกลับหน้าก่อนหน้า
    if (data.data.length === 0 && state.page > 1) {
      state.page = Math.max(1, data.total_pages);
      return loadProducts();
    }
    renderTable(data.data);
    renderPager(data.page, data.total_pages);
    $('summary').textContent = `พบสินค้า ${data.total} รายการ`;
  } catch (e) {
    $('productBody').innerHTML = `<tr><td colspan="6" class="empty">${esc(e.message)}</td></tr>`;
    $('pager').innerHTML = '';
    $('summary').textContent = '';
  }
}

function renderTable(items) {
  if (!items.length) {
    $('productBody').innerHTML = '<tr><td colspan="6" class="empty">ไม่พบสินค้า</td></tr>';
    return;
  }
  $('productBody').innerHTML = items.map((p) => `
    <tr>
      <td>${p.id}</td>
      <td>
        <div class="product-cell">
          ${p.thumbnail ? `<img class="thumb" src="${esc(p.thumbnail)}" alt="" loading="lazy">` : '<div class="thumb"></div>'}
          <div>
            <div class="product-title">${esc(p.title)}</div>
            <div class="product-brand">${esc(p.brand || '')}</div>
          </div>
        </div>
      </td>
      <td><span class="badge">${esc(p.category)}</span></td>
      <td class="num">${money(p.price)}</td>
      <td class="num ${p.stock <= 5 ? 'stock-low' : ''}">${p.stock}</td>
      <td class="actions">
        <button class="btn btn-ghost btn-sm" data-act="quick" data-id="${p.id}">ราคา/จำนวน</button>
        <button class="btn btn-ghost btn-sm" data-act="edit" data-id="${p.id}">แก้ไข</button>
        <button class="btn btn-danger btn-sm" data-act="delete" data-id="${p.id}" data-title="${esc(p.title)}">ลบ</button>
      </td>
    </tr>`).join('');
}

function renderPager(page, totalPages) {
  if (totalPages <= 1) { $('pager').innerHTML = ''; return; }
  const buttons = [`<button data-page="${page - 1}" ${page === 1 ? 'disabled' : ''}>ก่อนหน้า</button>`];
  const start = Math.max(1, page - 2);
  const end = Math.min(totalPages, page + 2);
  for (let i = start; i <= end; i++) {
    buttons.push(`<button data-page="${i}" class="${i === page ? 'active' : ''}">${i}</button>`);
  }
  buttons.push(`<button data-page="${page + 1}" ${page === totalPages ? 'disabled' : ''}>ถัดไป</button>`);
  $('pager').innerHTML = buttons.join('');
}

// ---------- ฟอร์มเพิ่ม / แก้ไข (POST / PUT) ----------
function openProductForm(product) {
  $('productForm').reset();
  $('formError').hidden = true;
  $('productDialogTitle').textContent = product ? 'แก้ไขสินค้า' : 'เพิ่มสินค้า';
  $('fId').value = product ? product.id : '';
  if (product) {
    $('fTitle').value = product.title;
    $('fCategory').value = product.category;
    $('fPrice').value = product.price;
    $('fStock').value = product.stock;
    $('fBrand').value = product.brand || '';
    $('fDescription').value = product.description || '';
    $('fThumbnail').value = product.thumbnail || '';
  }
  $('productDialog').showModal();
}

async function saveProduct(event) {
  event.preventDefault();
  const id = $('fId').value;
  const body = {
    title: $('fTitle').value.trim(),
    category: $('fCategory').value.trim(),
    price: Number($('fPrice').value),
    stock: parseInt($('fStock').value, 10),
    brand: $('fBrand').value.trim(),
    description: $('fDescription').value.trim(),
    thumbnail: $('fThumbnail').value.trim(),
  };
  $('btnSave').disabled = true;
  try {
    const { status, data } = await request(id ? `${API}?id=${id}` : API, {
      method: id ? 'PUT' : 'POST',
      body,
    });
    $('productDialog').close();
    toast(`${data.message} (${id ? 'PUT' : 'POST'} ${status})`, 'ok');
    await Promise.all([loadProducts(), loadCategories()]);
  } catch (e) {
    $('formError').textContent = e.message;
    $('formError').hidden = false;
  } finally {
    $('btnSave').disabled = false;
  }
}

async function editProduct(id) {
  try {
    const { data } = await request(`${API}?id=${id}`);
    openProductForm(data);
  } catch (e) {
    toast(e.message, 'error');
  }
}

// ---------- PATCH: แก้ไขราคา / จำนวน ----------
async function openQuickForm(id) {
  try {
    const { data } = await request(`${API}?id=${id}`);
    $('qId').value = data.id;
    $('quickName').textContent = data.title;
    $('qPrice').value = data.price;
    $('qStock').value = data.stock;
    $('quickDialog').showModal();
  } catch (e) {
    toast(e.message, 'error');
  }
}

async function saveQuick(event) {
  event.preventDefault();
  const id = $('qId').value;
  try {
    const { status, data } = await request(`${API}?id=${id}`, {
      method: 'PATCH',
      body: { price: Number($('qPrice').value), stock: parseInt($('qStock').value, 10) },
    });
    $('quickDialog').close();
    toast(`${data.message} (PATCH ${status})`, 'ok');
    loadProducts();
  } catch (e) {
    toast(e.message, 'error');
  }
}

// ---------- DELETE ----------
function askDelete(id, title) {
  pendingDeleteId = id;
  $('deleteText').textContent = `ต้องการลบ "${title}" ใช่หรือไม่? การลบไม่สามารถย้อนกลับได้`;
  $('deleteDialog').showModal();
}

async function confirmDelete() {
  if (pendingDeleteId === null) return;
  try {
    const { status, data } = await request(`${API}?id=${pendingDeleteId}`, { method: 'DELETE' });
    $('deleteDialog').close();
    toast(`${data.message} (DELETE ${status})`, 'ok');
    pendingDeleteId = null;
    await Promise.all([loadProducts(), loadCategories()]);
  } catch (e) {
    $('deleteDialog').close();
    toast(e.message, 'error');
  }
}

// ---------- DummyJSON ----------
let djItems = [];

function renderDj(items) {
  djItems = items;
  if (!items.length) {
    $('djBody').innerHTML = '<tr><td colspan="4" class="empty">ไม่พบสินค้า</td></tr>';
    return;
  }
  $('djBody').innerHTML = items.map((p, i) => `
    <tr>
      <td>
        <div class="product-cell">
          ${p.thumbnail ? `<img class="thumb" src="${esc(p.thumbnail)}" alt="" loading="lazy">` : '<div class="thumb"></div>'}
          <div>
            <div class="product-title">${esc(p.title)}</div>
            <div class="product-brand">${esc(p.brand || '')}</div>
          </div>
        </div>
      </td>
      <td><span class="badge">${esc(p.category)}</span></td>
      <td class="num">${money(p.price)}</td>
      <td class="actions"><button class="btn btn-primary btn-sm" data-dj-add="${i}">เพิ่มเข้าระบบ</button></td>
    </tr>`).join('');
}

async function loadDj(query) {
  $('djStatus').textContent = 'กำลังดึงข้อมูลจาก DummyJSON...';
  $('djBody').innerHTML = '';
  try {
    const url = query ? `${DJ}?action=search&q=${encodeURIComponent(query)}` : `${DJ}?action=list&limit=20`;
    const { data } = await request(url);
    $('djStatus').textContent = `พบ ${data.total} รายการ (แสดง ${data.data.length})`;
    renderDj(data.data);
  } catch (e) {
    $('djStatus').textContent = e.message;
  }
}

async function addFromDj(index) {
  const p = djItems[index];
  if (!p) return;
  try {
    const { status, data } = await request(API, {
      method: 'POST',
      body: {
        title: p.title, description: p.description, price: p.price,
        category: p.category, stock: p.stock, brand: p.brand, thumbnail: p.thumbnail,
      },
    });
    toast(`${data.message} (POST ${status})`, 'ok');
    await Promise.all([loadProducts(), loadCategories()]);
  } catch (e) {
    toast(e.message, 'error');
  }
}

async function importAllFromDj() {
  $('btnDjImportAll').disabled = true;
  try {
    const { data } = await request(`${DJ}?action=import`, { method: 'POST', body: { limit: 20, skip: 0 } });
    toast(`นำเข้า ${data.imported} รายการ (ข้ามที่ซ้ำ ${data.skipped})`, 'ok');
    await Promise.all([loadProducts(), loadCategories()]);
  } catch (e) {
    toast(e.message, 'error');
  } finally {
    $('btnDjImportAll').disabled = false;
  }
}

// ---------- ผูกอีเวนต์ ----------
let searchTimer = null;
$('searchInput').addEventListener('input', (e) => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => { state.q = e.target.value.trim(); state.page = 1; loadProducts(); }, 300);
});
$('categoryFilter').addEventListener('change', (e) => { state.category = e.target.value; state.page = 1; loadProducts(); });
$('sortSelect').addEventListener('change', (e) => {
  [state.sort, state.order] = e.target.value.split('|');
  state.page = 1;
  loadProducts();
});

$('btnAdd').addEventListener('click', () => openProductForm(null));
$('btnImport').addEventListener('click', () => { $('importDialog').showModal(); loadDj(''); });
$('productForm').addEventListener('submit', saveProduct);
$('quickForm').addEventListener('submit', saveQuick);
$('btnConfirmDelete').addEventListener('click', confirmDelete);

$('productBody').addEventListener('click', (e) => {
  const btn = e.target.closest('button[data-act]');
  if (!btn) return;
  const id = btn.dataset.id;
  if (btn.dataset.act === 'edit') editProduct(id);
  if (btn.dataset.act === 'quick') openQuickForm(id);
  if (btn.dataset.act === 'delete') askDelete(id, btn.dataset.title);
});

$('pager').addEventListener('click', (e) => {
  const btn = e.target.closest('button[data-page]');
  if (!btn || btn.disabled) return;
  state.page = Number(btn.dataset.page);
  loadProducts();
});

$('btnDjSearch').addEventListener('click', () => loadDj($('djSearch').value.trim()));
$('djSearch').addEventListener('keydown', (e) => { if (e.key === 'Enter') loadDj($('djSearch').value.trim()); });
$('btnDjImportAll').addEventListener('click', importAllFromDj);
$('djBody').addEventListener('click', (e) => {
  const btn = e.target.closest('button[data-dj-add]');
  if (btn) addFromDj(Number(btn.dataset.djAdd));
});

document.querySelectorAll('[data-close]').forEach((btn) => {
  btn.addEventListener('click', () => btn.closest('dialog').close());
});

// เริ่มทำงาน
loadCategories();
loadProducts();
