// The design's "Download PDF" project datasheet (project-details.html), ported as is: jsPDF, built from the page's
// [data-pdf-*] elements. Loaded only when the button is clicked. jsPDF's built-in font is Latin-only, so (as in the
// design) non-Latin text is transliterated or dropped.
import { jsPDF } from 'jspdf'

export default async function buildPDF(root) {
        function q(sel) {
          return root.querySelector(sel);
        }
        function qa(sel) {
          return Array.prototype.slice.call(root.querySelectorAll(sel));
        }
        function txt(sel) {
          return ((q(sel) || {}).textContent || "").trim();
        }

        /* Sanitize non-ASCII for jsPDF Helvetica */
        function sp(s) {
          if (!s) return "";
          var out = "";
          for (var i = 0; i < s.length; i++) {
            var c = s.charCodeAt(i);
            if (c >= 0x20 && c <= 0x7e) {
              out += s[i];
              continue;
            }
            switch (c) {
              case 0x2018:
              case 0x2019:
              case 0x201a:
              case 0x201b:
                out += "'";
                break;
              case 0x201c:
              case 0x201d:
              case 0x201e:
              case 0x201f:
                out += '"';
                break;
              case 0x2013:
                out += "-";
                break;
              case 0x2014:
                out += " - ";
                break;
              case 0x2026:
                out += "...";
                break;
              case 0x2022:
                out += "-";
                break;
              case 0x00a0:
                out += " ";
                break;
              case 0x00ae:
                out += "(R)";
                break;
              case 0x00a9:
                out += "(c)";
                break;
              case 0x00b2:
                out += "2";
                break;
              case 0x00b3:
                out += "3";
                break;
              case 0x00c0:
              case 0x00c1:
              case 0x00c2:
              case 0x00c3:
              case 0x00c4:
              case 0x00c5:
                out += "A";
                break;
              case 0x00e0:
              case 0x00e1:
              case 0x00e2:
              case 0x00e3:
              case 0x00e4:
              case 0x00e5:
                out += "a";
                break;
              case 0x00c7:
                out += "C";
                break;
              case 0x00e7:
                out += "c";
                break;
              case 0x00c8:
              case 0x00c9:
              case 0x00ca:
              case 0x00cb:
                out += "E";
                break;
              case 0x00e8:
              case 0x00e9:
              case 0x00ea:
              case 0x00eb:
                out += "e";
                break;
              case 0x00cc:
              case 0x00cd:
              case 0x00ce:
              case 0x00cf:
                out += "I";
                break;
              case 0x00ec:
              case 0x00ed:
              case 0x00ee:
              case 0x00ef:
                out += "i";
                break;
              case 0x00d1:
                out += "N";
                break;
              case 0x00f1:
                out += "n";
                break;
              case 0x00d2:
              case 0x00d3:
              case 0x00d4:
              case 0x00d5:
              case 0x00d6:
              case 0x00d8:
                out += "O";
                break;
              case 0x00f2:
              case 0x00f3:
              case 0x00f4:
              case 0x00f5:
              case 0x00f6:
              case 0x00f8:
                out += "o";
                break;
              case 0x00d9:
              case 0x00da:
              case 0x00db:
              case 0x00dc:
                out += "U";
                break;
              case 0x00f9:
              case 0x00fa:
              case 0x00fb:
              case 0x00fc:
                out += "u";
                break;
              case 0x00df:
                out += "ss";
                break;
              default:
                if (c >= 0xd800 && c <= 0xdbff) i++;
                break;
            }
          }
          return out;
        }

        function imgToDataURL(src) {
          return new Promise(function (resolve) {
            var img = new Image();
            img.crossOrigin = "anonymous";
            img.onload = function () {
              try {
                var canvas = document.createElement("canvas");
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;
                canvas.getContext("2d").drawImage(img, 0, 0);
                resolve(canvas.toDataURL("image/jpeg", 0.92));
              } catch (_) {
                resolve(null);
              }
            };
            img.onerror = function () {
              resolve(null);
            };
            img.src = src;
          });
        }


        async function buildPDF() {
          try {
                        var doc = new jsPDF({ unit: "mm", format: "a4" });
            var PW = doc.internal.pageSize.getWidth(); // 210
            var PH = doc.internal.pageSize.getHeight(); // 297
            var M = 16; // left/right margin
            var CW = PW - M * 2; // content width = 178
            var HDR = 12; // header height (px)
            var TOP = HDR + 6; // first content y after header = 18
            var BOT = PH - 13; // last safe y before footer = 284

            var RED = [200, 16, 46],
              INK = [13, 13, 13],
              GRAY = [105, 105, 105];
            var LGRAY = [170, 170, 170],
              LITE = [247, 245, 242],
              MID = [205, 205, 205];
            var WHITE = [255, 255, 255],
              DARK = [18, 15, 10];

            /* ── Collect data ── */
            var projName = sp(txt("[data-pdf-name]") || "Project");
            var devEl = q("[data-pdf-dev]");
            var logoTxt = sp(txt("[data-pdf-dev-logo]"));
            var devFull = sp(((devEl || {}).textContent || "").trim()).replace(
              /\s+/g,
              " ",
            );
            var developer = devFull.replace(logoTxt, "").trim();
            var locEl = q("[data-pdf-location]");
            var location = sp(
              ((locEl || {}).textContent || "").replace(/\s+/g, " ").trim(),
            );
            var status = sp(
              txt("[data-pdf-status]").replace(/\s+/g, " ").trim(),
            );
            var type = sp(txt("[data-pdf-type]"));

            var stats = qa("[data-pdf-stat]").map(function (el) {
              return {
                val: sp(
                  (
                    (el.querySelector("[data-pdf-val]") || {})
                      .textContent || ""
                  )
                    .replace(/\s+/g, " ")
                    .trim(),
                ),
                label: sp(
                  (
                    (el.querySelector("[data-pdf-label]") || {})
                      .textContent || ""
                  ).trim(),
                ),
              };
            });
            var descParts = qa("[data-pdf-desc] p")
              .map(function (p) {
                return sp(p.textContent.trim());
              })
              .filter(Boolean);
            var metrics = qa("[data-pdf-metric]").map(function (el) {
              return {
                val: sp(
                  (
                    (el.querySelector("[data-pdf-val]") || {})
                      .textContent || ""
                  )
                    .replace(/\s+/g, " ")
                    .trim(),
                ),
                label: sp(
                  (
                    (el.querySelector("[data-pdf-label]") || {})
                      .textContent || ""
                  ).trim(),
                ),
              };
            });
            var amenities = qa("[data-pdf-amenity]").map(function (el) {
              return sp(el.textContent.trim());
            });

            /* ── Hero image ── */
            var heroData = null;
            var bgEl = q("[data-pdf-hero]");
            if (bgEl) {
              var bgCSS = window.getComputedStyle(bgEl).backgroundImage;
              var bgMatch = bgCSS.match(/url\(["']?([^"')]+)["']?\)/);
              if (bgMatch) heroData = await imgToDataURL(bgMatch[1]);
            }

            /* ── y tracker + helpers ── */
            var y = TOP;

            function drawHeader() {
              doc.setFillColor.apply(doc, RED);
              doc.rect(0, 0, PW, 4, "F");
              doc.setFillColor.apply(doc, DARK);
              doc.rect(0, 4, PW, 8, "F");
              doc.setFont("helvetica", "bold");
              doc.setFontSize(7.5);
              doc.setTextColor.apply(doc, WHITE);
              doc.text("AKAR TOWN", M, 9.5);
              doc.setFont("helvetica", "normal");
              doc.setFontSize(6.5);
              doc.setTextColor.apply(doc, LGRAY);
              doc.text("Project Datasheet  |  Confidential", PW - M, 9.5, {
                align: "right",
              });
            }

            function drawFooter(num, total) {
              var fy = PH - 9;
              doc.setDrawColor.apply(doc, MID);
              doc.setLineWidth(0.2);
              doc.line(M, fy, PW - M, fy);
              doc.setFont("helvetica", "normal");
              doc.setFontSize(6.5);
              doc.setTextColor.apply(doc, GRAY);
              doc.text("AKAR Town Real Estate  |  " + projName, M, fy + 4);
              doc.text(num + " / " + total, PW - M, fy + 4, { align: "right" });
              doc.setTextColor.apply(doc, MID);
              doc.text(
                "All figures are indicative and subject to change without notice.",
                PW / 2,
                fy + 4,
                { align: "center" },
              );
            }

            function newPage() {
              doc.addPage();
              drawHeader();
              y = TOP;
            }

            /* Reserve at least h mm; start a new page if not enough room */
            function need(h) {
              if (y + h > BOT) newPage();
            }

            function sectionHeading(text) {
              need(14);
              doc.setFont("helvetica", "bold");
              doc.setFontSize(8);
              doc.setTextColor.apply(doc, RED);
              doc.text(text.toUpperCase(), M, y);
              doc.setDrawColor.apply(doc, RED);
              doc.setLineWidth(0.5);
              doc.line(
                M,
                y + 1.2,
                M + doc.getTextWidth(text.toUpperCase()),
                y + 1.2,
              );
              y += 9;
            }

            var LH = 5.5; // body line-height

            function bodyFont() {
              doc.setFont("helvetica", "normal");
              doc.setFontSize(9);
              doc.setTextColor(60, 60, 60);
            }

            /* Draw paragraphs, page-breaking line by line */
            function drawParas(parts) {
              bodyFont();
              for (var pi = 0; pi < parts.length; pi++) {
                var para = parts[pi].trim();
                if (!para) continue;
                doc.setFontSize(9);
                var lines = doc.splitTextToSize(para, CW);
                for (var li = 0; li < lines.length; li++) {
                  need(LH);
                  doc.text(lines[li], M, y);
                  y += LH;
                }
                if (pi < parts.length - 1) y += LH * 0.6;
              }
            }

            /* ════════════════════════════════════════
             PAGE 1 — COVER
          ════════════════════════════════════════ */
            drawHeader();
            var IMG_H = 88;
            if (heroData) {
              doc.addImage(heroData, "JPEG", 0, HDR, PW, IMG_H, "", "FAST");
            } else {
              doc.setFillColor.apply(doc, DARK);
              doc.rect(0, HDR, PW, IMG_H, "F");
              doc.setFillColor.apply(doc, RED);
              doc.rect(0, HDR, 5, IMG_H, "F");
            }
            y = HDR + IMG_H + 12;

            /* Badge row */
            var badge = [status, type].filter(Boolean).join("  ·  ");
            if (badge) {
              doc.setFont("helvetica", "bold");
              doc.setFontSize(7);
              doc.setTextColor.apply(doc, RED);
              doc.text(badge.toUpperCase(), M, y);
              y += 13;
            }

            /* Project name */
            doc.setFont("helvetica", "bold");
            doc.setFontSize(26);
            doc.setTextColor.apply(doc, INK);
            var nameLines = doc.splitTextToSize(projName, CW);
            doc.text(nameLines, M, y);
            y += nameLines.length * 9.5 + 2;

            /* Developer + location on one line */
            var devLoc = [developer, location].filter(Boolean).join("  |  ");
            if (devLoc) {
              doc.setFont("helvetica", "normal");
              doc.setFontSize(8.5);
              doc.setTextColor.apply(doc, GRAY);
              doc.text(devLoc, M, y);
              y += 5;
            }

            /* Thin rule */
            doc.setDrawColor.apply(doc, MID);
            doc.setLineWidth(0.2);
            doc.line(M, y + 2, PW - M, y + 2);
            y += 8;

            /* Stats strip */
            if (stats.length) {
              var SW = CW / stats.length,
                SH = 26;
              doc.setFillColor.apply(doc, DARK);
              doc.roundedRect(M, y, CW, SH, 3, 3, "F");
              doc.setFillColor.apply(doc, RED);
              doc.roundedRect(M, y, 4, SH, 2, 2, "F");
              stats.forEach(function (s, i) {
                var sx = M + 4 + i * SW + SW / 2;
                if (i > 0) {
                  doc.setDrawColor(38, 35, 28);
                  doc.setLineWidth(0.2);
                  doc.line(M + 4 + i * SW, y + 5, M + 4 + i * SW, y + SH - 5);
                }
                doc.setFont("helvetica", "bold");
                doc.setFontSize(11);
                doc.setTextColor.apply(doc, WHITE);
                doc.text(s.val, sx, y + 11, { align: "center" });
                doc.setFont("helvetica", "normal");
                doc.setFontSize(6);
                doc.setTextColor.apply(doc, LGRAY);
                doc.text(s.label.toUpperCase(), sx, y + 19, {
                  align: "center",
                });
              });
              y += SH + 10;
            }

            /* First description paragraph — fill remaining cover space */
            sectionHeading("About This Project");
            bodyFont();
            if (descParts.length > 0) {
              doc.setFontSize(9);
              var p0 = doc.splitTextToSize(descParts[0], CW);
              var p0avail = Math.max(Math.floor((BOT - y) / LH) - 1, 0);
              var p0show = p0.slice(0, p0avail);
              p0show.forEach(function (ln, li) {
                var trunc =
                  li === p0show.length - 1 && p0.length > p0show.length;
                doc.text(trunc ? ln.trimEnd() + "..." : ln, M, y);
                y += LH;
              });
            }

            /* ════════════════════════════════════════
             PAGE 2 — Full Description + Key Figures
          ════════════════════════════════════════ */
            newPage();
            sectionHeading("Project Description");
            drawParas(descParts);
            y += 10;

            /* Key Figures — 2-column banded table */
            if (metrics.length > 0) {
              var MFW = CW / 2,
                MRH = 26,
                MR = Math.ceil(metrics.length / 2);
              need(MRH * MR + 18);
              sectionHeading("Key Figures");

              /* Draw alternating row bands */
              for (var ri = 0; ri < MR; ri++) {
                if (ri % 2 === 0) {
                  doc.setFillColor.apply(doc, LITE);
                  doc.rect(M, y + ri * MRH, CW, MRH, "F");
                }
              }
              /* Left red accent bar */
              doc.setFillColor.apply(doc, RED);
              doc.rect(M, y, 3, MR * MRH, "F");

              metrics.forEach(function (m, i) {
                var col = i % 2,
                  row = Math.floor(i / 2);
                var mx = M + 3 + col * MFW + 8;
                var my = y + row * MRH;
                /* Value */
                doc.setFont("helvetica", "bold");
                doc.setFontSize(16);
                doc.setTextColor.apply(doc, RED);
                doc.text(m.val, mx, my + 10);
                /* Label */
                doc.setFont("helvetica", "bold");
                doc.setFontSize(7.5);
                doc.setTextColor.apply(doc, INK);
                doc.text(m.label.toUpperCase(), mx, my + 18);
                /* Column divider */
                if (col === 0 && i + 1 < metrics.length) {
                  doc.setDrawColor.apply(doc, MID);
                  doc.setLineWidth(0.2);
                  doc.line(M + 3 + MFW, my + 4, M + 3 + MFW, my + MRH - 4);
                }
              });
              y += MR * MRH + 12;
            }

            /* ════════════════════════════════════════
             PAGE 3 — Amenities + Contact CTA
          ════════════════════════════════════════ */
            if (amenities.length > 0) {
              var ACW = CW / 3,
                ARH = 8.5,
                AR = Math.ceil(amenities.length / 3);
              need(AR * ARH + 22);
              sectionHeading("Amenities & Features");

              /* Subtle background */
              doc.setFillColor(250, 249, 246);
              doc.rect(M, y, CW, AR * ARH + 4, "F");

              amenities.forEach(function (a, i) {
                var col = i % 3,
                  row = Math.floor(i / 3);
                var ax = M + col * ACW + 2,
                  ay = y + row * ARH + ARH * 0.75;
                if (ay > BOT) return;
                doc.setFillColor.apply(doc, RED);
                doc.circle(ax + 1.5, ay - 1.5, 1, "F");
                doc.setFont("helvetica", "normal");
                doc.setFontSize(8.5);
                doc.setTextColor(45, 45, 45);
                doc.text(a, ax + 5, ay);
              });
              y += AR * ARH + 16;
            }

            /* Contact CTA card */
            var CTA_H = 36;
            need(CTA_H + 4);
            doc.setFillColor.apply(doc, DARK);
            doc.roundedRect(M, y, CW, CTA_H, 3, 3, "F");
            doc.setFillColor.apply(doc, RED);
            doc.roundedRect(M, y, 4, CTA_H, 2, 2, "F");

            doc.setFont("helvetica", "bold");
            doc.setFontSize(13);
            doc.setTextColor.apply(doc, WHITE);
            doc.text("Interested in this project?", M + 10, y + 12);
            doc.setFont("helvetica", "normal");
            doc.setFontSize(8);
            doc.setTextColor(190, 185, 178);
            doc.text(
              "Contact our advisors to register your interest or schedule a site visit.",
              M + 10,
              y + 20,
            );
            doc.setFont("helvetica", "bold");
            doc.setFontSize(9);
            doc.setTextColor.apply(doc, RED);
            doc.text(sp(root.dataset.pdfPhone || ""), M + 10, y + 30);
            doc.setFont("helvetica", "normal");
            doc.setFontSize(8);
            doc.setTextColor(130, 125, 118);
            doc.text(
              sp([root.dataset.pdfEmail, location.host].filter(Boolean).join("  |  ")),
              PW - M - 4,
              y + 30,
              { align: "right" },
            );

            /* ── Stamp footers on every page ── */
            var total = doc.internal.getNumberOfPages();
            for (var p = 1; p <= total; p++) {
              doc.setPage(p);
              drawFooter(p, total);
            }

            var filename =
              projName.replace(/[^a-z0-9]/gi, "-").toLowerCase() +
              "-datasheet.pdf";
            doc.save(filename);
          } finally {
            // The loading state is the caller's (Alpine).
          }
        }

        await buildPDF()
}
