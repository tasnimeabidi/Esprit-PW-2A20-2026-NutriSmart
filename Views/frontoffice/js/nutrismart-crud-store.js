/**
 * Store CRUD partagé Front/BackOffice.
 * - Priorité API MySQL (mêmes données partout)
 * - Repli localStorage si API indisponible
 */
(function () {
  "use strict";

  var LS_KEY = "nutrismart.crud.cache.v1";
  var state = {
    apiReady: false,
    data: {
      planRepas: [],
      repas: [],
      programmeSportif: [],
      recettes: [],
    },
  };

  function safeArray(v) {
    return Array.isArray(v) ? v : [];
  }

  function toStr(v) {
    return v == null ? "" : String(v);
  }

  function sortByIdAsc(rows) {
    rows.sort(function (a, b) {
      return Number(a.id || 0) - Number(b.id || 0);
    });
    return rows;
  }

  function defaultRecettes() {
    return [
      { id: "1", nom: "Petit-dejeuner equilibre", calories_totales: "420", caloriesTotales: "420" },
      { id: "2", nom: "Salade composee", calories_totales: "350", caloriesTotales: "350" },
      { id: "3", nom: "Poulet grille et legumes", calories_totales: "520", caloriesTotales: "520" },
      { id: "4", nom: "Pates sauce tomate", calories_totales: "610", caloriesTotales: "610" },
      { id: "5", nom: "Soupe de legumes", calories_totales: "280", caloriesTotales: "280" },
      { id: "6", nom: "Omelette aux fines herbes", calories_totales: "330", caloriesTotales: "330" },
      { id: "7", nom: "Riz thon et mais", calories_totales: "470", caloriesTotales: "470" },
      { id: "8", nom: "Yaourt fruits et granola", calories_totales: "260", caloriesTotales: "260" },
    ];
  }

  function mergeRecettes(base, extras) {
    var out = [];
    var seen = {};
    safeArray(base).concat(safeArray(extras)).forEach(function (r) {
      if (!r) return;
      var id = toStr(r.id).trim();
      if (!id || seen[id]) return;
      seen[id] = true;
      out.push({
        id: id,
        nom: toStr(r.nom),
        instructions: toStr(r.instructions),
        calories_totales: toStr(r.calories_totales != null ? r.calories_totales : r.caloriesTotales),
        caloriesTotales: toStr(r.caloriesTotales != null ? r.caloriesTotales : r.calories_totales),
      });
    });
    return sortByIdAsc(out);
  }

  function storageLoad() {
    try {
      var raw = localStorage.getItem(LS_KEY);
      if (!raw) return;
      var parsed = JSON.parse(raw);
      if (!parsed || typeof parsed !== "object") return;
      state.data.planRepas = safeArray(parsed.planRepas);
      state.data.repas = safeArray(parsed.repas);
      state.data.programmeSportif = safeArray(parsed.programmeSportif);
      state.data.recettes = safeArray(parsed.recettes);
      purgeOrphansRepasEtProgramme();
      storageSave();
    } catch (_e) {}
  }

  function storageSave() {
    try {
      localStorage.setItem(LS_KEY, JSON.stringify(state.data));
    } catch (_e) {}
  }

  function apiBase() {
    var b = typeof window !== "undefined" ? window.NUTRISMART_API_BASE : "";
    b = toStr(b).trim();
    // Auto-détection API quand la page n'a pas défini NUTRISMART_API_BASE.
    // Cas typique: /projetNutriSmart/Views/frontoffice/*.html -> /projetNutriSmart/api
    if (!b && typeof window !== "undefined" && window.location && window.location.protocol !== "file:") {
      var path = toStr(window.location.pathname);
      var iViews = path.indexOf("/Views/");
      if (iViews > 0) {
        b = path.substring(0, iViews) + "/api";
      }
    }
    return b.replace(/\/+$/, "");
  }

  function apiUrl(path) {
    var base = apiBase();
    if (!base) return "";
    return base + "/" + path.replace(/^\/+/, "");
  }

  function requestJson(method, url, body) {
    return fetch(url, {
      method: method,
      headers: { "Content-Type": "application/json" },
      body: body == null ? undefined : JSON.stringify(body),
    }).then(function (res) {
      return res
        .json()
        .catch(function () {
          return {};
        })
        .then(function (json) {
          if (!res.ok) {
            var msg = (json && json.error) || ("HTTP " + res.status);
            throw new Error(msg);
          }
          return json;
        });
    });
  }

  function byId(rows, id) {
    var sid = toStr(id);
    return rows.find(function (r) {
      return toStr(r.id) === sid;
    }) || null;
  }

  function upsert(rows, row) {
    var sid = toStr(row && row.id);
    if (!sid) return;
    var i = rows.findIndex(function (r) {
      return toStr(r.id) === sid;
    });
    if (i >= 0) rows[i] = row;
    else rows.push(row);
    sortByIdAsc(rows);
  }

  function removeById(rows, id) {
    var sid = toStr(id);
    var i = rows.findIndex(function (r) {
      return toStr(r.id) === sid;
    });
    if (i >= 0) rows.splice(i, 1);
  }

  /** Id plan affiché / comparé : retire tout caractère # (ASCII ou pleine chasse). */
  function normalizePlanIdKey(v) {
    return toStr(v).trim().replace(/[#\uFF03]/g, "").trim();
  }

  /** Côté cache : miroir MCD repas.id_plan (idPlan) = plan_repas.id — toutes ces lignes repas doivent disparaître si le plan est supprimé. */
  function cascadeOnPlanDelete(planId) {
    var sid = normalizePlanIdKey(planId);
    if (!sid) return;
    state.data.repas = state.data.repas.filter(function (r) {
      return normalizePlanIdKey(r.idPlan) !== sid;
    });
    state.data.programmeSportif = state.data.programmeSportif.filter(function (p) {
      return normalizePlanIdKey(p.idPlan) !== sid;
    });
  }

  /** Repas / programmes dont id_plan ne correspond plus à aucun plan (cache ou anciennes données). */
  function purgeOrphansRepasEtProgramme() {
    var planKeys = {};
    state.data.planRepas.forEach(function (p) {
      var k = normalizePlanIdKey(p && p.id);
      if (k) planKeys[k] = true;
    });
    state.data.repas = state.data.repas.filter(function (r) {
      return !!planKeys[normalizePlanIdKey(r && r.idPlan)];
    });
    state.data.programmeSportif = state.data.programmeSportif.filter(function (p) {
      return !!planKeys[normalizePlanIdKey(p && p.idPlan)];
    });
  }

  function buildEntity(entityName, endpoint) {
    function list() {
      return state.data[entityName].slice();
    }

    function get(id) {
      return byId(state.data[entityName], id);
    }

    function create(payload) {
      if (state.apiReady) {
        return requestJson("POST", apiUrl(endpoint), payload).then(function (row) {
          upsert(state.data[entityName], row);
          storageSave();
          return row;
        }).catch(function (e) {
          return { error: e.message || "Erreur création." };
        });
      }
      var nextId = 1;
      state.data[entityName].forEach(function (r) {
        nextId = Math.max(nextId, Number(r.id || 0) + 1);
      });
      var localRow = Object.assign({ id: String(nextId) }, payload || {});
      upsert(state.data[entityName], localRow);
      storageSave();
      return Promise.resolve(localRow);
    }

    function update(id, payload) {
      if (state.apiReady) {
        return requestJson("PUT", apiUrl(endpoint + "?id=" + encodeURIComponent(toStr(id))), payload).then(function (row) {
          upsert(state.data[entityName], row);
          storageSave();
          return row;
        }).catch(function (e) {
          return { error: e.message || "Erreur mise à jour." };
        });
      }
      var ex = get(id);
      if (!ex) return Promise.resolve({ error: "Enregistrement introuvable." });
      var localRow = Object.assign({}, ex, payload || {}, { id: toStr(id) });
      upsert(state.data[entityName], localRow);
      storageSave();
      return Promise.resolve(localRow);
    }

    function del(id) {
      if (state.apiReady) {
        return requestJson("DELETE", apiUrl(endpoint + "?id=" + encodeURIComponent(toStr(id))))
          .then(function (row) {
            removeById(state.data[entityName], id);
            if (entityName === "planRepas") {
              cascadeOnPlanDelete(id);
            }
            storageSave();
            return row;
          })
          .catch(function (e) {
            return { error: e.message || "Erreur suppression." };
          });
      }
      var ex = get(id);
      if (!ex) return Promise.resolve({ error: "Enregistrement introuvable." });
      removeById(state.data[entityName], id);
      if (entityName === "planRepas") {
        cascadeOnPlanDelete(id);
      }
      storageSave();
      return Promise.resolve({ ok: true });
    }

    return {
      list: list,
      get: get,
      create: create,
      update: update,
      delete: del,
    };
  }

  function loadAllFromApi() {
    return Promise.all([
      requestJson("GET", apiUrl("plan-repas.php")),
      requestJson("GET", apiUrl("repas.php")),
      requestJson("GET", apiUrl("programme-sportif.php")),
      requestJson("GET", apiUrl("recettes.php")),
    ]).then(function (all) {
      state.data.planRepas = sortByIdAsc(safeArray(all[0]));
      state.data.repas = sortByIdAsc(safeArray(all[1]));
      state.data.programmeSportif = sortByIdAsc(safeArray(all[2]));
      purgeOrphansRepasEtProgramme();
      state.data.repas = sortByIdAsc(state.data.repas);
      state.data.programmeSportif = sortByIdAsc(state.data.programmeSportif);
      var apiRecettes = sortByIdAsc(safeArray(all[3]));
      var existingByNom = {};
      apiRecettes.forEach(function (r) {
        var k = toStr(r.nom).trim().toLowerCase();
        if (k) existingByNom[k] = true;
      });
      var missingDefaults = defaultRecettes().filter(function (r) {
        var k = toStr(r.nom).trim().toLowerCase();
        return k && !existingByNom[k];
      });
      if (!missingDefaults.length) {
        state.data.recettes = apiRecettes;
        storageSave();
        return;
      }
      var chain = Promise.resolve();
      missingDefaults.forEach(function (r) {
        chain = chain.then(function () {
          return requestJson("POST", apiUrl("recettes.php"), {
            nom: toStr(r.nom),
            instructions: toStr(r.instructions),
            caloriesTotales: toStr(r.caloriesTotales || r.calories_totales),
            status: "active",
          }).catch(function () {
            return null;
          });
        });
      });
      return chain.then(function () {
        return requestJson("GET", apiUrl("recettes.php"))
          .then(function (rows) {
            state.data.recettes = sortByIdAsc(safeArray(rows));
            storageSave();
          })
          .catch(function () {
            state.data.recettes = apiRecettes;
            storageSave();
          });
      });
    });
  }

  function healthcheck() {
    return requestJson("GET", apiUrl("health.php")).then(function (h) {
      window.NUTRISMART_CRUD_LAST_HEALTH = h;
      return !!(h && h.ok && h.database);
    }).catch(function (e) {
      window.NUTRISMART_CRUD_LAST_HEALTH = { ok: false, error: e.message || "health failed" };
      return false;
    });
  }

  var NutriSmartCRUD = {
    init: function () {
      storageLoad();
      if (!apiBase()) {
        state.apiReady = false;
        return Promise.resolve(false);
      }
      return healthcheck().then(function (ok) {
        if (!ok) {
          state.apiReady = false;
          return false;
        }
        return loadAllFromApi()
          .then(function () {
            state.apiReady = true;
            return true;
          })
          .catch(function (e) {
            window.NUTRISMART_CRUD_LAST_HEALTH = { ok: false, error: e.message || "load failed" };
            state.apiReady = false;
            return false;
          });
      });
    },
    isApi: function () {
      return !!state.apiReady;
    },
    seedDemo: function () {
      storageLoad();
      state.data.recettes = mergeRecettes(state.data.recettes, defaultRecettes());
      storageSave();
    },
    planRepas: buildEntity("planRepas", "plan-repas.php"),
    repas: buildEntity("repas", "repas.php"),
    programmeSportif: buildEntity("programmeSportif", "programme-sportif.php"),
    recettes: {
      list: function () {
        return state.data.recettes.slice();
      },
      get: function (id) {
        return byId(state.data.recettes, id);
      },
    },
  };

  window.NutriSmartCRUD = NutriSmartCRUD;
})();
