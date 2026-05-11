const fs = require('fs');
const path = require('path');

const srcFile = path.join(__dirname, 'nutrismart-website.html');
const srcHtml = fs.readFileSync(srcFile, 'utf8');

// Extract NAV
const navMatch = srcHtml.match(/<!-- NAV -->[\s\S]*?<\/nav>/);
if (!navMatch) throw new Error("Could not find NAV block");
const navBlock = navMatch[0];

// Extract SIDEBAR
const sidebarMatch = srcHtml.match(/<!-- SIDEBAR MENU -->[\s\S]*?<\/div>\s*<\/div>/);
if (!sidebarMatch) throw new Error("Could not find SIDEBAR block");
let sidebarBlock = sidebarMatch[0];
// Ensure we got exactly the sidebar block (up to the closing of sidebarMenu)
const sidebarIdx = srcHtml.indexOf(sidebarMatch[0]);
// Actually simpler:
sidebarBlock = srcHtml.substring(srcHtml.indexOf('<!-- SIDEBAR MENU -->'), srcHtml.indexOf('<!-- HERO -->'));

// Extract JS
const jsMatch = srcHtml.match(/\/\/ --- SIDEBAR MENU LOGIC ---[\s\S]*?}\n/);
const searchJsMatch = srcHtml.match(/\/\/ --- SEARCH LOGIC ---[\s\S]*?}\n/);

const scriptBlock = `
    // --- SIDEBAR MENU LOGIC ---
    function openSidebar() {
      document.getElementById('sidebarMenu').classList.add('active');
      document.getElementById('sidebarOverlay').classList.add('active');
      document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    function closeSidebar() {
      document.getElementById('sidebarMenu').classList.remove('active');
      document.getElementById('sidebarOverlay').classList.remove('active');
      document.body.style.overflow = 'auto';
    }

    // --- SEARCH LOGIC ---
    function performSiteSearch(query) {
      if (!query || query.trim() === '') return;
      query = query.toLowerCase().trim();
      
      closeSidebar();
      
      const searchableElements = document.querySelectorAll('.feat-card, .plate-item, .how-step, h1, h2, h3, h4');
      let found = false;
      
      for (const el of searchableElements) {
        if (el.innerText.toLowerCase().includes(query)) {
          el.scrollIntoView({ behavior: 'smooth', block: 'center' });
          
          const originalOutline = el.style.outline;
          const originalOutlineOffset = el.style.outlineOffset;
          el.style.outline = '4px solid var(--orange)';
          el.style.outlineOffset = '4px';
          
          setTimeout(() => {
            el.style.outline = originalOutline;
            el.style.outlineOffset = originalOutlineOffset;
          }, 2000);
          
          found = true;
          break;
        }
      }
      
      if (!found) {
        alert('Aucun résultat trouvé pour : ' + query);
      }
      document.getElementById('siteSearch').value = '';
    }
`;

const filesToPatch = [
    'login.html',
    'register.html',
    'nutrismart-home.html',
    'profile.html',
    'aliments.html',
    'recette.html',
    'smart_picks.html',
    'contact.html',
    'reset_password.html'
];

filesToPatch.forEach(file => {
    const filePath = path.join(__dirname, file);
    if (!fs.existsSync(filePath)) return;
    
    let content = fs.readFileSync(filePath, 'utf8');
    
    // 1. Remove existing <nav> block
    // Some files have <nav id="navbar">, some have <nav>
    content = content.replace(/<nav[^>]*>[\s\S]*?<\/nav>/, '');
    
    // 2. Remove existing sidebar blocks if any
    content = content.replace(/<!-- SIDEBAR MENU -->[\s\S]*?<\/div>\s*<\/div>/, '');
    content = content.replace(/<div class="sidebar-overlay"[^>]*>[\s\S]*?<\/div>\s*<\/div>/, '');
    
    // 3. Insert NAV and SIDEBAR right after <body>
    const insertHeader = `\n${navBlock}\n${sidebarBlock}\n`;
    content = content.replace(/<body>/i, `<body>${insertHeader}`);
    
    // 4. Insert script logic before </body>
    // First, remove it if it already exists to avoid duplicates
    content = content.replace(/\/\/ --- SIDEBAR MENU LOGIC ---[\s\S]*?performSiteSearch[^}]*?}[^}]*?}/, '');
    
    // Insert into the bottom script tag, or add a new script tag before </body>
    // Try to find if there's a script tag at the bottom
    const scriptTagMatch = content.match(/<script>[\s\S]*?<\/script>\s*<\/body>/i);
    if (scriptTagMatch) {
        content = content.replace(/(<\/script>\s*<\/body>)/i, `${scriptBlock}\n$1`);
    } else {
        content = content.replace(/(<\/body>)/i, `\n<script>\n${scriptBlock}\n</script>\n$1`);
    }
    
    fs.writeFileSync(filePath, content);
    console.log(`Patched ${file}`);
});
