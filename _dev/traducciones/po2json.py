import json, re, sys

def unq(s):
    return json.loads('"' + s + '"')  # el escape de PO es compatible con JSON para estos casos

def parse(path):
    entries, cur, field = [], {}, None
    def flush():
        nonlocal cur
        if cur.get("msgid") is not None:
            entries.append(cur)
        cur = {}
    for raw in open(path, encoding="utf-8").read().split("\n"):
        line = raw.strip()
        if line.startswith("#~") or (line.startswith("#") and not line.startswith('"')):
            if line.startswith("#,") or line.startswith("#:") or line.startswith("#.") or line.startswith("# "):
                if "fuzzy" in line and line.startswith("#,"):
                    cur["fuzzy"] = True
            continue
        if not line:
            flush(); field = None; continue
        m = re.match(r'^(msgctxt|msgid_plural|msgid|msgstr(?:\[(\d+)\])?)\s+"(.*)"$', line)
        if m:
            key = m.group(1)
            if key == "msgid" and cur.get("msgid") is not None:
                flush()
            if key.startswith("msgstr["):
                field = ("msgstr", int(m.group(2)))
                cur.setdefault("msgstr", {})[int(m.group(2))] = unq(m.group(3))
            elif key == "msgstr":
                field = ("msgstr", 0)
                cur.setdefault("msgstr", {})[0] = unq(m.group(3))
            else:
                field = (key, None)
                cur[key] = unq(m.group(3))
            continue
        m = re.match(r'^"(.*)"$', line)
        if m and field:
            k, i = field
            if k == "msgstr":
                cur["msgstr"][i] += unq(m.group(1))
            else:
                cur[k] += unq(m.group(1))
    flush()
    return entries

def build(path):
    es = parse(path)
    head = next(e for e in es if e["msgid"] == "")
    hdr = head["msgstr"][0]
    pf = re.search(r"Plural-Forms:\s*([^\n]+)", hdr).group(1).strip()
    data = {"": {"domain": "messages", "plural_forms": pf, "lang": "es"}}
    for e in es:
        if e["msgid"] == "" or e.get("fuzzy"):
            continue
        ms = e.get("msgstr", {})
        vals = [ms[i] for i in sorted(ms)]
        if not any(v for v in vals):
            continue
        key = (e["msgctxt"] + "\x04" if "msgctxt" in e else "") + e["msgid"]
        data[key] = vals
    return {"domain": "messages", "locale_data": {"messages": data}}

if __name__ == "__main__":
    src, dst = sys.argv[1], sys.argv[2]
    out = build(src)
    json.dump(out, open(dst, "w", encoding="utf-8"), ensure_ascii=False, indent=2)
    print("claves:", len(out["locale_data"]["messages"]) - 1)
