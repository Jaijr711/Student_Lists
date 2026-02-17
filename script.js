
/* ═══════════════════════════════════════════════════════════════════
   CONSTANTS & STATE
═══════════════════════════════════════════════════════════════════ */
const COURSES = ["BSIT","BSCS","BSIS","BSED"];
const ROOMS   = ["Room 101","Room 201","Room 301","Room 302","Room 303","Lab 1","Lab 2","Lab 3","Lab 4","Studio 1"];
const DAYS    = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
const TIMES   = ["07:00","07:30","08:00","08:30","09:00","09:30","10:00","10:30","11:00","11:30","12:00","13:00","13:30","14:00","14:30","15:00","15:30","16:00"];

let STATE = {
    view: 'landing',
    user: null, // { username, role, name, avatar, ... }
    activeTab: 'overview', // for dashboard navigation
    data: {
        teachers: [],
        loads: [],
        students: [],
        subjects: [],
        attendance: [],
        myLoads: [] // for teacher
    },
    temp: {} // For form data, modal state, etc.
};

/* ═══════════════════════════════════════════════════════════════════
   API & UTILS
═══════════════════════════════════════════════════════════════════ */
async function api(action, payload = {}) {
    try {
        const res = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ...payload })
        });
        const data = await res.json();
        if (data.error) {
            showToast(data.error, 'err');
            return null;
        }
        return data;
    } catch (e) {
        console.error(e);
        showToast("Network Error", 'err');
        return null;
    }
}

function uid() { return Math.random().toString(36).slice(2, 9).toUpperCase(); }

function showToast(msg, type = 'info') {
    const C = { ok:"#34d399", err:"#fb7185", warn:"#fbbf24", info:"#38bdf8" };
    const c = C[type] || C.info;
    const ico = { ok:"✓", err:"✕", warn:"⚠", info:"ℹ" }[type] || "ℹ";
    
    const div = document.createElement('div');
    div.style.cssText = `position:fixed;top:16px;right:16px;z-index:9999;animation:slideR .3s ease;
      background:#0a1525;border:1px solid ${c}30;border-left:3px solid ${c};
      border-radius:10px;padding:11px 16px;max-width:380px;display:flex;gap:10px;align-items:flex-start;
      box-shadow:0 16px 48px rgba(0,0,0,.75)`;
    div.innerHTML = `
      <span style="color:${c};font-weight:800;font-size:14px;margin-top:1px">${ico}</span>
      <span style="font-size:13px;flex:1;line-height:1.6">${msg}</span>
      <button class="btn" style="background:none;color:#2e4a6a;font-size:17px" onclick="this.parentElement.remove()">×</button>
    `;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 4000);
}

// Simple React-like setState for simple rerenders
function setState(updates) {
    Object.assign(STATE, updates);
    renderApp();
}

function setView(view, updates = {}) {
    STATE.view = view;
    Object.assign(STATE, updates);
    renderApp();
}

/* ═══════════════════════════════════════════════════════════════════
   RENDERERS
═══════════════════════════════════════════════════════════════════ */
function renderApp() {
    const root = document.getElementById('root');
    root.innerHTML = '';

    if (STATE.view === 'landing') root.appendChild(renderLanding());
    else if (STATE.view === 'login') root.appendChild(renderLogin());
    else if (STATE.view === 'app') renderShell(root);
}

/* ── LANDING ───────────────────────────────────────────────────── */
function renderLanding() {
    const div = document.createElement('div');
    div.style.cssText = `min-height:100vh;display:flex;flex-direction:column;align-items:center;
      justify-content:center;padding:24px;position:relative;overflow:hidden`;

    // Background logic omitted for brevity, adding main content
    div.innerHTML = `
      <div class="a1" style="display:inline-flex;align-items:center;gap:8px;padding:5px 16px;
        background:rgba(56,189,248,.07);border:1px solid rgba(56,189,248,.18);border-radius:20px;margin-bottom:24px">
        <span style="width:6px;height:6px;border-radius:50%;background:#38bdf8;animation:blink 1.6s infinite"></span>
        <span class="mono" style="font-size:9.5px;color:#38bdf8;letter-spacing:.12em">
          ISCC INTEGRATED PORTAL • SY 2025–2026 • ISCC-REG4-01
        </span>
      </div>
      <h1 class="a2" style="font-weight:900;font-size:clamp(24px,4.5vw,46px);text-align:center;line-height:1.1;margin-bottom:8px;
        background:linear-gradient(140deg,#cdd9ec 0%,#7aaac8 45%,#38bdf8 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent">
        Ilocos Sur Community College
      </h1>
      <p class="a3 mono" style="color:#2e4a6a;font-size:12px;margin-bottom:48px;letter-spacing:.04em">
        Enrollment · Faculty · Attendance · Academic Load Management
      </p>
      <div class="a4" id="roles" style="display:flex;gap:14px;flex-wrap:wrap;justify-content:center;margin-bottom:48px"></div>
    `;

    const roles = [
        { role:"Registrar",color:"#38bdf8",icon:"📋",desc:"Accounts · Loads · Scheduling · Enrollment",cred:"registrar01 / reg2024" },
        { role:"Teacher",  color:"#34d399",icon:"📚",desc:"My Load · Attendance",                       cred:"teacher01 / tch2024" },
        { role:"Student",  color:"#fbbf24",icon:"🎓",desc:"Profile · Schedule · Attendance",            cred:"student01 / stu2024" }
    ];

    const rolesContainer = div.querySelector('#roles');
    roles.forEach(r => {
        const card = document.createElement('div');
        card.className = "card-hover"; // helper class
        card.style.cssText = `width:215px;padding:22px;border-radius:14px;cursor:pointer;
          background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.07);transition:all .2s ease`;
        card.innerHTML = `
          <div style="font-size:30px;margin-bottom:10px">${r.icon}</div>
          <div style="font-weight:800;font-size:15px;color:#cdd9ec;margin-bottom:5px">${r.role}</div>
          <div style="font-size:11px;color:#2e4a6a;line-height:1.55;margin-bottom:12px">${r.desc}</div>
          <div class="mono" style="font-size:9px;color:${r.color};background:${r.color}0f;padding:3px 8px;border-radius:4px;display:inline-block">${r.cred}</div>
        `;
        card.onclick = () => { STATE.temp.role = r.role; setView('login'); };
        card.onmouseenter = () => { card.style.background = r.color+'0b'; card.style.borderColor = r.color+'44'; card.querySelector('div:nth-child(2)').style.color = r.color; };
        card.onmouseleave = () => { card.style.background = 'rgba(255,255,255,.02)'; card.style.borderColor = 'rgba(255,255,255,.07)'; card.querySelector('div:nth-child(2)').style.color = '#cdd9ec'; };
        rolesContainer.appendChild(card);
    });

    return div;
}

/* ── LOGIN ─────────────────────────────────────────────────────── */
function renderLogin() {
    // Re-render Landing in background
    const root = document.getElementById('root');
    root.appendChild(renderLanding());

    const role = STATE.temp.role;
    const RC = { Registrar:"#38bdf8", Teacher:"#34d399", Student:"#fbbf24" };
    const c = RC[role];

    const overlay = document.createElement('div');
    overlay.style.cssText = `position:fixed;inset:0;background:rgba(0,0,0,.82);display:flex;
      align-items:center;justify-content:center;z-index:300;backdrop-filter:blur(8px)`;

    overlay.innerHTML = `
      <div style="background:#0a1525;border:1px solid ${c}20;border-radius:18px;width:370px;max-width:94vw;
        overflow:hidden;box-shadow:0 0 48px ${c}18,0 28px 56px rgba(0,0,0,.7);animation:fadeUp .28s ease">
        <div style="height:3px;background:linear-gradient(90deg,${c}66,${c})"></div>
        <div style="padding:22px 22px 26px">
          <div style="font-weight:800;font-size:15px;margin-bottom:4px">Sign In — ${role}</div>
          <div class="mono" style="font-size:9px;color:#2e4a6a;margin-bottom:18px">🔒 ROLE LOCKED ON ENTRY — ISCC PORTAL</div>

          <div style="margin-bottom:12px"><label>Username</label><input id="u" style="width:100%" placeholder="username" /></div>
          <div style="margin-bottom:20px"><label>Password</label><input id="p" type="password" style="width:100%" placeholder="password" /></div>

          <button id="loginBtn" class="btn" style="width:100%;padding:11px;border-radius:8px;font-size:14px;
            background:linear-gradient(135deg,${c}99,${c});color:#05090f;display:flex;align-items:center;justify-content:center;gap:8px;
            box-shadow:0 4px 18px ${c}30">🔑 Enter as ${role}</button>

          <button id="backBtn" class="btn" style="width:100%;margin-top:8px;padding:9px;border-radius:8px;
            background:transparent;color:#2e4a6a;font-size:12px;border:1px solid rgba(255,255,255,.06)">← Back to Portal</button>
        </div>
      </div>
    `;

    overlay.querySelector('#backBtn').onclick = () => setView('landing');
    overlay.querySelector('#loginBtn').onclick = async () => {
        const u = overlay.querySelector('#u').value;
        const p = overlay.querySelector('#p').value;
        const btn = overlay.querySelector('#loginBtn');
        btn.innerHTML = `<span style="animation:spin .7s linear infinite;border:2px solid #000;border-top-color:transparent;border-radius:50%;width:14px;height:14px"></span> Verifying...`;
        
        const res = await api('login', { username: u, password: p, role });
        if (res && res.success) {
            STATE.user = res.user;
            // Default tabs
            if (role === 'Registrar') STATE.activeTab = 'overview';
            if (role === 'Teacher') STATE.activeTab = 'load';
            if (role === 'Student') STATE.activeTab = 'profile';
            showToast(`Locked in as ${role} — Welcome, ${res.user.name.split(" ")[0]}!`, "ok");
            setView('app');
        } else {
            btn.innerHTML = `🔑 Enter as ${role}`;
            showToast(res?.message || "Login failed", 'err');
        }
    };

    return overlay;
}

/* ── SHELL ─────────────────────────────────────────────────────── */
function renderShell(root) {
    const user = STATE.user;
    const RC = { Registrar:"#38bdf8", Teacher:"#34d399", Student:"#fbbf24" };
    const c = RC[user.role];

    const NAVS = {
        Registrar: [
          { id:"overview",  icon:"📊", label:"Overview" },
          { id:"teachers",  icon:"👤", label:"Teacher Accounts" },
          { id:"loads",     icon:"📋", label:"Academic Loads" },
          { id:"schedules", icon:"🗓", label:"Room & Scheduling" },
          { id:"enroll",    icon:"📝", label:"Enrollment Form" },
          { id:"students",  icon:"🗂", label:"Student Records" },
          { id:"report",    icon:"📈", label:"Attendance Reports" },
        ],
        Teacher: [
          { id:"load",    icon:"📊", label:"My Load" },
          { id:"attend",  icon:"📋", label:"Take Attendance" },
          { id:"history", icon:"📅", label:"Attendance History" },
        ],
        Student: [
          { id:"profile",  icon:"🪪", label:"My Profile" },
          { id:"schedule", icon:"📅", label:"My Schedule" },
          { id:"attend",   icon:"📋", label:"My Attendance" },
        ]
    };
    const nav = NAVS[user.role];

    const container = document.createElement('div');
    container.style.cssText = `display:flex;min-height:100vh`;
    
    // Sidebar
    const side = document.createElement('div');
    side.style.cssText = `width:238px;min-height:100vh;background:#060b16;border-right:1px solid rgba(255,255,255,.05);
      display:flex;flex-direction:column;flex-shrink:0;position:sticky;top:0;height:100vh;overflow:hidden`;
    
    side.innerHTML = `
      <div style="padding:13px 10px 11px;border-bottom:1px solid rgba(255,255,255,.05)">
        <div style="display:flex;align-items:center;gap:8px">
          <div style="width:30px;height:30px;border-radius:7px;background:linear-gradient(135deg,${c}77,${c});
            display:flex;align-items:center;justify-content:center;font-size:14px">🏫</div>
          <div><div style="font-weight:900;font-size:11.5px;line-height:1.1">ISCC Portal</div>
          <div class="mono" style="font-size:7.5px;color:${c};letter-spacing:.1em">${user.role.toUpperCase()}</div></div>
        </div>
      </div>
      <div id="nav" style="flex:1;padding:8px 5px;overflow-y:auto"></div>
      <div style="padding:8px 5px;border-top:1px solid rgba(255,255,255,.05)">
        <button id="logout" class="btn" style="width:100%;padding:8px 10px;border-radius:6px;
          background:rgba(251,113,133,.06);color:#fb7185;border:1px solid rgba(251,113,133,.12);font-size:11px;
          display:flex;align-items:center;gap:6px"><span>🚪</span> Sign Out</button>
      </div>
    `;
    
    // Render Nav items
    const navContainer = side.querySelector('#nav');
    nav.forEach(n => {
        const btn = document.createElement('button');
        const active = STATE.activeTab === n.id;
        btn.className = 'btn';
        btn.style.cssText = `width:100%;padding:8px 10px;border-radius:6px;margin-bottom:1px;
          display:flex;align-items:center;gap:8px;font-size:12.5px;
          background:${active ? c+'12' : 'transparent'};
          color:${active ? c : '#2e4a6a'};
          border:1px solid ${active ? c+'22' : 'transparent'};
          font-weight:${active ? 700 : 400}`;
        btn.innerHTML = `<span style="font-size:14px">${n.icon}</span> ${n.label}`;
        btn.onclick = () => { STATE.activeTab = n.id; renderApp(); };
        navContainer.appendChild(btn);
    });

    side.querySelector('#logout').onclick = () => { STATE.user = null; setView('landing'); showToast('Session ended.'); };

    // Main Content
    const main = document.createElement('div');
    main.style.cssText = `flex:1;min-width:0`;
    main.innerHTML = `
      <div style="padding:11px 20px;border-bottom:1px solid rgba(255,255,255,.05);
        background:rgba(6,11,22,.9);backdrop-filter:blur(12px);position:sticky;top:0;z-index:100;
        display:flex;align-items:center;justify-content:space-between">
        <div>
          <div style="font-weight:800;font-size:15px">${nav.find(n=>n.id===STATE.activeTab)?.label}</div>
          <div class="mono" style="font-size:8.5px;color:#1a3050;margin-top:2px">ISCC • ${user.role.toUpperCase()} • SY 2025–2026</div>
        </div>
        <span class="tag t-sky">${user.role}</span>
      </div>
      <div id="content" style="padding:20px"></div>
    `;

    container.appendChild(side);
    container.appendChild(main);
    root.appendChild(container);

    // Route content
    const content = main.querySelector('#content');
    if (user.role === 'Registrar') renderRegistrarContent(content);
    else if (user.role === 'Teacher') renderTeacherContent(content);
    else if (user.role === 'Student') renderStudentContent(content);
}

/* ── REGISTRAR LOGIC ───────────────────────────────────────────── */
async function renderRegistrarContent(container) {
    const tab = STATE.activeTab;

    // Overview
    if (tab === 'overview') {
        const stats = await api('get_overview_stats');
        container.innerHTML = `
          <div style="max-width:900px">
            <h2 class="a1" style="font-weight:900;font-size:21px;margin-bottom:4px">Registrar Control Center</h2>
            <p style="color:#2e4a6a;font-size:13px;margin-bottom:18px">${STATE.user.name} · ISCC</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:11px;margin-bottom:22px">
              ${StatCard("👤","Teachers",stats.teachers,`${stats.active_teachers} active`,"#38bdf8",1)}
              ${StatCard("📋","Loads Set",stats.loads,"Assigned","#34d399",2)}
              ${StatCard("🎓","Students",stats.students,"Enrolled","#fbbf24",3)}
              ${StatCard("📚","Subjects",stats.subjects,"Masterlist","#a78bfa",4)}
              ${StatCard("🏫","Rooms",ROOMS.length,"Spaces","#fb7185",5)}
            </div>
            <div class="card a6" style="padding:18px 22px">
              <div class="sec-hd">▸ REGISTRAR WORKFLOW</div>
              <div style="font-size:13px;color:#4a6a8a">1. Create Accounts → 2. Assign Loads → 3. Schedule Rooms → 4. Enroll Students</div>
            </div>
          </div>
        `;
    }

    // Teachers
    else if (tab === 'teachers') {
        const teachers = await api('get_teachers') || [];
        STATE.data.teachers = teachers;
        
        container.innerHTML = `
          <div style="max-width:900px">
             <div style="display:flex;justify-content:space-between;margin-bottom:18px">
               <h2 style="font-weight:900;font-size:20px">Teacher Accounts</h2>
               <button id="addTch" class="btn" style="padding:9px 20px;border-radius:8px;background:linear-gradient(135deg,#0369a1,#38bdf8);color:#05090f;font-size:13px">＋ Add Teacher</button>
             </div>
             <div class="card a2" style="padding:3px 0">
               <table>
                 <thead><tr><th>ID</th><th>Name</th><th>User</th><th>Dept</th><th>Status</th><th>Actions</th></tr></thead>
                 <tbody id="tchBody"></tbody>
               </table>
             </div>
          </div>
        `;
        
        const tbody = container.querySelector('#tchBody');
        teachers.forEach(t => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
               <td class="mono" style="font-size:10px;color:#2e4a6a">${t.id}</td>
               <td style="font-weight:700;font-size:13px">${t.name}</td>
               <td class="mono" style="color:#38bdf8;font-size:11px">${t.username}</td>
               <td><span class="tag t-slt">${t.dept}</span></td>
               <td><span class="tag ${t.status==='Active'?'t-grn':'t-red'}">${t.status}</span></td>
               <td><button class="btn t-toggle" style="font-size:10px;padding:3px 8px;background:rgba(255,255,255,.05);color:#cdd9ec;border-radius:4px">Toggle Status</button></td>
            `;
            tr.querySelector('.t-toggle').onclick = async () => {
                await api('toggle_teacher_status', { id: t.id });
                renderApp();
            };
            tbody.appendChild(tr);
        });

        container.querySelector('#addTch').onclick = () => {
            const m = createModal("Create Teacher Account", `
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div style="grid-column:span 2"><label>Name</label><input id="tn" placeholder="Full Name"></div>
                <div><label>Username</label><input id="tu"></div>
                <div><label>Password</label><input id="tp" type="password"></div>
                <div style="grid-column:span 2"><label>Email</label><input id="te"></div>
                <div><label>Dept</label><select id="td"><option>IT</option><option>GE</option></select></div>
              </div>
              <button id="saveT" class="btn" style="width:100%;margin-top:16px;padding:10px;background:#38bdf8;color:#000;border-radius:6px">Create</button>
            `);
            m.querySelector('#saveT').onclick = async () => {
                await api('add_teacher', {
                    id: 'T'+uid(), name: m.querySelector('#tn').value,
                    username: m.querySelector('#tu').value, password: m.querySelector('#tp').value,
                    email: m.querySelector('#te').value, dept: m.querySelector('#td').value,
                    status: 'Active', avatar: 'T', created_at: new Date().toISOString().split('T')[0]
                });
                m.remove();
                renderApp();
            };
        };
    }

    // Loads
    else if (tab === 'loads') {
        const [loads, teachers, subjects] = await Promise.all([
             api('get_loads'), api('get_teachers'), api('get_subjects')
        ]);
        STATE.data.loads = loads;

        container.innerHTML = `
          <div style="max-width:980px">
             <div style="display:flex;justify-content:space-between;margin-bottom:18px">
               <h2 style="font-weight:900;font-size:20px">Academic Load Assignment</h2>
               <button id="assignL" class="btn" style="padding:9px 20px;border-radius:8px;background:linear-gradient(135deg,#059669,#34d399);color:#05090f;font-size:13px">＋ Assign Load</button>
             </div>
             <div class="card a3" style="padding:3px 0">
               <table>
                 <thead><tr><th>Teacher</th><th>Subject</th><th>Section</th><th>Schedule</th><th>Action</th></tr></thead>
                 <tbody id="loadBody"></tbody>
               </table>
             </div>
          </div>
        `;

        const tbody = container.querySelector('#loadBody');
        loads.forEach(l => {
            const t = teachers.find(x => x.id === l.teacher_id);
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td style="font-weight:600;font-size:12px">${t?.name || l.teacher_id}</td>
              <td><div style="font-weight:700;font-size:12px">${l.subject_code}</div></td>
              <td><span class="tag t-vio">${l.section}</span></td>
              <td>${l.day ? `<span class="tag t-grn">${l.day} ${l.start_time}-${l.end_time}</span>` : '<span class="tag t-amb">Unscheduled</span>'}</td>
              <td><button class="btn del-load" style="font-size:10px;color:#fb7185">Remove</button></td>
            `;
            tr.querySelector('.del-load').onclick = async () => { await api('delete_load', { id: l.id }); renderApp(); };
            tbody.appendChild(tr);
        });

        container.querySelector('#assignL').onclick = () => {
             const m = createModal("Assign Load", `
               <div style="display:grid;gap:12px">
                 <div><label>Teacher</label><select id="lt">${teachers.map(t=>`<option value="${t.id}">${t.name}</option>`).join('')}</select></div>
                 <div><label>Subject</label><select id="ls">${subjects.map(s=>`<option value="${s.code}">${s.code} - ${s.name}</option>`).join('')}</select></div>
                 <div><label>Section</label><select id="lsec"><option>S3A</option><option>S3B</option><option>S2A</option></select></div>
               </div>
               <button id="saveL" class="btn" style="width:100%;margin-top:16px;padding:10px;background:#34d399;color:#000;border-radius:6px">Assign</button>
             `);
             m.querySelector('#saveL').onclick = async () => {
                 const sCode = m.querySelector('#ls').value;
                 const sub = subjects.find(s=>s.code===sCode);
                 await api('assign_load', {
                     id:'L'+uid(), teacher_id: m.querySelector('#lt').value,
                     subject_code: sCode, course: sub.course, year: sub.year,
                     section: m.querySelector('#lsec').value, sem:'1st', sy:'2025-2026'
                 });
                 m.remove();
                 renderApp();
             };
        };
    }

    // Room Scheduling
    else if (tab === 'schedules') {
        const loads = await api('get_loads');
        container.innerHTML = `
          <div style="max-width:1000px">
             <h2 style="font-weight:900;font-size:20px;margin-bottom:18px">Room & Schedule Logistics</h2>
             <div class="card a3" style="padding:3px 0">
               <table>
                 <thead><tr><th>Sub/Sec</th><th>Room</th><th>Day</th><th>Time</th><th>Action</th></tr></thead>
                 <tbody id="schBody"></tbody>
               </table>
             </div>
          </div>
        `;
        const tbody = container.querySelector('#schBody');
        loads.forEach(l => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td><div style="font-weight:700;font-size:12px">${l.subject_code}</div><div class="tag t-vio">${l.section}</div></td>
              <td style="font-size:12px">${l.room}</td>
              <td>${l.day || '—'}</td>
              <td class="mono" style="font-size:10px">${l.start_time ? l.start_time+'-'+l.end_time : '—'}</td>
              <td><button class="btn set-sch" style="padding:3px 10px;border-radius:5px;background:rgba(251,191,36,.1);color:#fbbf24;border:1px solid rgba(251,191,36,.2);font-size:11px">Edit</button></td>
            `;
            tr.querySelector('.set-sch').onclick = () => {
                const m = createModal("Set Schedule", `
                   <div style="display:grid;gap:12px">
                     <div><label>Room</label><select id="sr">${ROOMS.map(r=>`<option>${r}</option>`).join('')}</select></div>
                     <div><label>Day</label><select id="sd">${DAYS.map(d=>`<option>${d}</option>`).join('')}</select></div>
                     <div style="display:flex;gap:10px">
                       <div style="flex:1"><label>Start</label><select id="ss">${TIMES.map(t=>`<option>${t}</option>`).join('')}</select></div>
                       <div style="flex:1"><label>End</label><select id="se">${TIMES.map(t=>`<option>${t}</option>`).join('')}</select></div>
                     </div>
                   </div>
                   <button id="saveSch" class="btn" style="width:100%;margin-top:16px;padding:10px;background:#fbbf24;color:#000;border-radius:6px">Save</button>
                `);
                // Pre-fill
                if(l.room!=='TBD') m.querySelector('#sr').value = l.room;
                if(l.day) m.querySelector('#sd').value = l.day;

                m.querySelector('#saveSch').onclick = async () => {
                    await api('update_load_schedule', {
                        id: l.id, room: m.querySelector('#sr').value,
                        day: m.querySelector('#sd').value,
                        start: m.querySelector('#ss').value, end: m.querySelector('#se').value
                    });
                    m.remove();
                    renderApp();
                };
            };
            tbody.appendChild(tr);
        });
    }

    // Enrollment
    else if (tab === 'enroll') {
        container.innerHTML = `
          <div style="max-width:800px">
            <h2 class="a1" style="font-weight:900;font-size:20px;margin-bottom:18px">Enrollment Form (ISCC-REG4-01)</h2>
            <div class="card a1" style="padding:22px">
              <div class="sec-hd">▸ STUDENT INFORMATION</div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div><label>Surname</label><input id="es"></div>
                <div><label>Given Name</label><input id="eg"></div>
                <div><label>Course</label><select id="ec">${COURSES.map(c=>`<option>${c}</option>`).join('')}</select></div>
                <div><label>Year</label><select id="ey"><option value="1">1</option><option value="2">2</option><option value="3">3</option></select></div>
                <div><label>Section</label><select id="esec"><option>S3A</option><option>S3B</option><option>S2A</option></select></div>
              </div>
              <button id="subEnroll" class="btn" style="margin-top:20px;padding:10px 20px;background:#38bdf8;color:#000;border-radius:6px;font-weight:800">Enroll Student</button>
            </div>
          </div>
        `;
        container.querySelector('#subEnroll').onclick = async () => {
            const es = container.querySelector('#es').value;
            const eg = container.querySelector('#eg').value;
            if(!es || !eg) return showToast("Name required", "warn");
            await api('register_student', {
                id: `${new Date().getFullYear()}-${uid()}`, surname: es, given: eg, middle:'',
                course: container.querySelector('#ec').value, year: container.querySelector('#ey').value,
                section: container.querySelector('#esec').value, status: 'Enrolled'
                // other fields simplified
            });
            showToast("Enrolled!", "ok");
            container.querySelector('#es').value = "";
            container.querySelector('#eg').value = "";
        };
    }

    // Students
    else if (tab === 'students') {
        const students = await api('get_students');
        container.innerHTML = `
          <div style="max-width:940px">
             <h2 style="font-weight:900;font-size:20px;margin-bottom:18px">Student Records</h2>
             <div class="card a2" style="padding:3px 0">
               <table>
                 <thead><tr><th>ID</th><th>Name</th><th>Course</th><th>Sec</th><th>Status</th></tr></thead>
                 <tbody id="stuBody"></tbody>
               </table>
             </div>
          </div>
        `;
        const tbody = container.querySelector('#stuBody');
        students.forEach(s => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
               <td class="mono" style="font-size:10px;color:#2e4a6a">${s.id}</td>
               <td style="font-weight:700;font-size:13px">${s.surname}, ${s.given}</td>
               <td><span class="tag t-sky">${s.course}</span></td>
               <td><span class="tag t-vio">${s.section}</span></td>
               <td><span class="tag t-grn">${s.status}</span></td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Reports
    else if (tab === 'report') {
        const [loads, logs, students] = await Promise.all([api('get_loads'), api('get_attendance_logs'), api('get_students')]);
        // Simple View
        container.innerHTML = `
            <div style="max-width:980px">
                <h2 style="font-weight:900;font-size:20px;margin-bottom:18px">Attendance Reports</h2>
                <div class="card" style="padding:20px">
                   <p>Raw Logs Count: ${logs.length}</p>
                   <p style="font-size:12px;color:#4a6a8a">Detailed report generation is simplified in this version.</p>
                </div>
            </div>
        `;
    }
}

/* ── TEACHER LOGIC ────────────────────────────────────────────── */
async function renderTeacherContent(container) {
    const tab = STATE.activeTab;
    const myLoads = await api('get_my_loads', { teacher_id: STATE.user.teacherId });

    if (tab === 'load') {
        container.innerHTML = `
          <div style="max-width:840px">
             <h2 style="font-weight:900;font-size:21px;margin-bottom:18px">My Academic Load</h2>
             <div class="card a5" style="padding:3px 0">
               <table>
                 <thead><tr><th>Subject</th><th>Section</th><th>Schedule</th><th>Room</th></tr></thead>
                 <tbody id="myLBody"></tbody>
               </table>
             </div>
          </div>
        `;
        const tbody = container.querySelector('#myLBody');
        myLoads.forEach(l => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
               <td><span class="tag t-grn">${l.subject_code}</span></td>
               <td><span class="tag t-vio">${l.section}</span></td>
               <td>${l.day ? l.day+' '+l.start_time : '—'}</td>
               <td>${l.room}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    else if (tab === 'attend') {
        if (!myLoads.length) return container.innerHTML = "No loads assigned.";
        // Selector logic simplified: just first load for now or simple UI
        container.innerHTML = `
           <div style="max-width:860px">
              <h2 style="font-weight:900;font-size:20px;margin-bottom:18px">Take Attendance</h2>
              <div style="display:flex;gap:10px;margin-bottom:15px;flex-wrap:wrap" id="lbtns"></div>
              <div id="attArea"></div>
           </div>
        `;

        const btnC = container.querySelector('#lbtns');
        const area = container.querySelector('#attArea');

        myLoads.forEach(l => {
             const btn = document.createElement('button');
             btn.className = 'btn';
             btn.style.cssText = `padding:7px 13px;border-radius:7px;background:rgba(255,255,255,.03);color:#2e4a6a;border:1px solid rgba(255,255,255,.07)`;
             btn.textContent = `${l.subject_code} - ${l.section}`;
             btn.onclick = async () => {
                 // Get students for this section
                 // We need a helper or just get all students and filter by section in JS for simplicity?
                 // Let's rely on API if possible or just get all students for now.
                 const allStu = await api('get_students');
                 const secStu = allStu.filter(s => s.section === l.section);

                 area.innerHTML = `
                    <div class="card" style="padding:16px 20px">
                       <div style="margin-bottom:10px;font-weight:700">${l.subject_code} · ${l.section}</div>
                       <input type="date" id="adate" value="${new Date().toISOString().split('T')[0]}" style="margin-bottom:10px">
                       <table>
                         <thead><tr><th>Name</th><th>Status</th></tr></thead>
                         <tbody>${secStu.map(s => `
                            <tr>
                               <td>${s.surname}, ${s.given}</td>
                               <td>
                                 <select id="stat_${s.id}" style="padding:4px">
                                   <option>Present</option><option>Late</option><option>Absent</option>
                                 </select>
                               </td>
                            </tr>
                         `).join('')}</tbody>
                       </table>
                       <button id="svAtt" class="btn" style="margin-top:14px;padding:9px 22px;border-radius:8px;background:#34d399;color:#000">Save</button>
                    </div>
                 `;

                 area.querySelector('#svAtt').onclick = async () => {
                     const date = area.querySelector('#adate').value;
                     const logs = secStu.map(s => ({
                         id: 'A'+Date.now()+s.id, studentId: s.id, loadId: l.id,
                         date: date, timeIn: '08:00', status: document.getElementById('stat_'+s.id).value
                     }));
                     await api('save_attendance', { logs });
                     showToast("Saved!", "ok");
                 };
             };
             btnC.appendChild(btn);
        });
    }
    
    else if (tab === 'history') {
        container.innerHTML = "<div>Attendance History functionality is similar to reports.</div>";
    }
}

/* ── STUDENT LOGIC ────────────────────────────────────────────── */
async function renderStudentContent(container) {
    const tab = STATE.activeTab;
    const studentId = STATE.user.studentId; // Ensure this is set in login

    if (tab === 'profile') {
        const p = await api('get_student_profile', { id: studentId });
        container.innerHTML = `
           <div style="max-width:820px">
              <h2 class="a1" style="font-weight:900;font-size:21px;margin-bottom:18px">My Profile</h2>
              <div class="card a2" style="padding:18px 22px">
                 <div class="sec-hd">▸ PERSONAL INFORMATION</div>
                 <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px">
                    <div><div class="mono" style="font-size:8px;color:#2e4a6a;margin-bottom:3px">NAME</div><div style="font-weight:600">${p.surname}, ${p.given}</div></div>
                    <div><div class="mono" style="font-size:8px;color:#2e4a6a;margin-bottom:3px">ID</div><div style="font-weight:600">${p.id}</div></div>
                    <div><div class="mono" style="font-size:8px;color:#2e4a6a;margin-bottom:3px">COURSE</div><div style="font-weight:600">${p.course}</div></div>
                    <div><div class="mono" style="font-size:8px;color:#2e4a6a;margin-bottom:3px">SECTION</div><div style="font-weight:600">${p.section}</div></div>
                 </div>
              </div>
           </div>
        `;
    }

    else if (tab === 'schedule') {
        const sched = await api('get_student_schedule', { student_id: studentId });
        container.innerHTML = `
           <div style="max-width:820px">
              <h2 class="a1" style="font-weight:900;font-size:20px;margin-bottom:18px">My Class Schedule</h2>
              <div style="display:flex;gap:10px;flex-wrap:wrap">
                ${sched.map(l => `
                   <div class="card" style="padding:13px 16px;min-width:210px;border-left:3px solid #38bdf8">
                      <div class="mono" style="font-size:11px;color:#38bdf8;font-weight:600;margin-bottom:3px">${l.subject_code}</div>
                      <div class="tag t-slt">${l.day||'TBD'} ${l.start_time||''}</div>
                      <div class="tag t-vio">📍 ${l.room||'TBD'}</div>
                   </div>
                `).join('')}
              </div>
           </div>
        `;
    }

    else if (tab === 'attend') {
        const logs = await api('get_student_attendance', { student_id: studentId });
        container.innerHTML = `
           <div style="max-width:820px">
              <h2 class="a1" style="font-weight:900;font-size:20px;margin-bottom:18px">My Attendance</h2>
              <div class="card" style="padding:14px">
                 ${logs.length ? logs.map(l => `
                    <div style="display:flex;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,.05);padding:8px 0">
                       <span>${l.date}</span>
                       <span class="tag ${l.status==='Present'?'t-grn':l.status==='Late'?'t-amb':'t-red'}">${l.status}</span>
                    </div>
                 `).join('') : 'No attendance records.'}
              </div>
           </div>
        `;
    }
}

/* ── HELPERS ───────────────────────────────────────────────────── */
function StatCard(icon, label, value, sub, color, idx) {
    return `
    <div class="card a${idx}" style="padding:16px 18px;position:relative;overflow:hidden">
      <div style="position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,${color}33,${color})"></div>
      <div style="font-size:20px;margin-bottom:8px">${icon}</div>
      <div style="font-family:'Fira Code',monospace;font-size:9.5px;color:#2e4a6a;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px">${label}</div>
      <div style="font-weight:900;font-size:22px;color:${color};line-height:1">${value}</div>
      <div style="font-size:11px;color:#1e3a52;margin-top:4px">${sub}</div>
    </div>`;
}

function createModal(title, contentHTML) {
    const div = document.createElement('div');
    div.style.cssText = `position:fixed;inset:0;background:rgba(0,0,0,.78);display:flex;align-items:center;
      justify-content:center;z-index:500;backdrop-filter:blur(6px)`;
    div.innerHTML = `
      <div style="background:#0a1525;border:1px solid rgba(255,255,255,.1);border-radius:16px;width:560px;
        max-width:95vw;max-height:88vh;overflow:auto;box-shadow:0 32px 64px rgba(0,0,0,.7);animation:fadeUp .28s ease">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:17px 22px;
          border-bottom:1px solid rgba(255,255,255,.07)">
          <span style="font-weight:800;font-size:15px">${title}</span>
          <button class="btn close-m" style="background:rgba(255,255,255,.05);color:#5a7a9a;width:28px;height:28px;border-radius:50%;font-size:15px">×</button>
        </div>
        <div style="padding:22px">${contentHTML}</div>
      </div>
    `;
    div.querySelector('.close-m').onclick = () => div.remove();
    document.body.appendChild(div);
    return div;
}

// Init
renderApp();
