/* CTF IPSS - Admin panel */
(function(){
  const cfg=window.CTF_ADMIN||{apiBase:'/api'};
  const root=document.documentElement;
  if(localStorage.getItem('ctf-theme')) root.setAttribute('data-theme',localStorage.getItem('ctf-theme'));
  function syncIcons(){
    const dark=root.getAttribute('data-theme')==='dark';
    document.querySelectorAll('.icon-sun').forEach(e=>e.style.display=dark?'none':'');
    document.querySelectorAll('.icon-moon').forEach(e=>e.style.display=dark?'':'none');
  }
  syncIcons();
  const tt=document.getElementById('theme-toggle');
  if(tt) tt.addEventListener('click',()=>{
    const n=root.getAttribute('data-theme')==='dark'?'light':'dark';
    root.setAttribute('data-theme',n);localStorage.setItem('ctf-theme',n);syncIcons();
  });
  document.querySelectorAll('.user-menu-trigger').forEach(b=>b.addEventListener('click',e=>{
    e.stopPropagation();b.parentElement.classList.toggle('open');
  }));
  document.addEventListener('click',()=>document.querySelectorAll('.user-menu.open').forEach(m=>m.classList.remove('open')));
  // Tabs
  const tabBtns=document.querySelectorAll('.admin-tab-btn');
  const panels=document.querySelectorAll('.admin-tab-panel');
  tabBtns.forEach(btn=>btn.addEventListener('click',()=>{
    tabBtns.forEach(b=>{b.classList.remove('active');b.setAttribute('aria-selected','false');});
    panels.forEach(p=>{p.classList.remove('active');p.hidden=true;});
    btn.classList.add('active');btn.setAttribute('aria-selected','true');
    const p=document.getElementById('tab-'+btn.dataset.tab);
    if(p){p.classList.add('active');p.hidden=false;}
  }));
  // Modals genéricos
  function openModal(id){const m=document.getElementById(id);if(m)m.hidden=false;}
  function closeModals(){document.querySelectorAll('.modal').forEach(m=>m.hidden=true);}
  document.querySelectorAll('.modal-close,.modal-overlay').forEach(el=>el.addEventListener('click',closeModals));
  document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModals();});
  // Ver evidencia
  document.querySelectorAll('.view-evidence').forEach(b=>b.addEventListener('click',()=>{
    document.getElementById('evidence-content').textContent=b.dataset.evidence||'(vacía)';
    openModal('evidence-modal');
  }));
  // Ver estudiante (usa API ranking para detalle)
  document.querySelectorAll('.view-student').forEach(b=>b.addEventListener('click',async()=>{
    const body=document.getElementById('student-modal-body');
    body.innerHTML='<p class="text-muted">Cargando…</p>';openModal('student-modal');
    try{
      const r=await fetch(cfg.apiBase+'/ranking.php?limit=200');
      const d=await r.json();
      const s=(d.ranking||[]).find(x=>String(x.usuario_id)===String(b.dataset.userId));
      if(!s){body.innerHTML='<p>No encontrado.</p>';return;}
      let rows=(s.soluciones||[]).map(x=>'<tr><td>'+escapeHtml(x.titulo)+'</td><td>+'+x.puntos_obtenidos+'</td><td>'+escapeHtml(x.fecha_resolucion||'')+'</td></tr>').join('');
      body.innerHTML='<p><strong>'+escapeHtml(s.nombre)+'</strong> — '+escapeHtml(s.email)+'</p>'
        +'<p>Puntaje: <strong>'+s.puntaje_total+'</strong> | Completados: '+s.desafios_completados+'</p>'
        +'<div class="table-container"><table class="admin-table"><thead><tr><th>Desafío</th><th>Puntos</th><th>Fecha</th></tr></thead><tbody>'+(rows||'<tr><td colspan="3">Sin resoluciones</td></tr>')+'</tbody></table></div>';
    }catch(e){body.innerHTML='<p class="text-muted">Error al cargar.</p>';}
  }));
  function escapeHtml(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
})();
