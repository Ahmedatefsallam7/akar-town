// The design's "Download PDF" property datasheet (property-details.html), ported as is: jsPDF + autotable, built
// from the page's [data-pdf-*] elements. Loaded only when the button is clicked. jsPDF's built-in font is Latin-only,
// so (as in the design) non-Latin text is transliterated or dropped.
import { jsPDF } from 'jspdf'
import 'jspdf-autotable'

export default async function buildPDF(root) {

        /* ── Helpers ── */
        function q(sel) {
          return root.querySelector(sel);
        }
        function qa(sel) {
          return [...root.querySelectorAll(sel)];
        }
        function txt(sel) {
          return (q(sel)?.textContent || "").trim();
        }

        /* jsPDF Helvetica only supports ASCII (0x20-0x7E). Characters outside
         that range get double-encoded as garbled garbage. We use charCodeAt()
         numeric comparisons -- immune to any file-encoding quirks -- to convert
         every non-ASCII character to a safe ASCII equivalent before jsPDF sees it. */
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
              case 0x2122:
                out += "TM";
                break;
              case 0x00ae:
                out += "(R)";
                break;
              case 0x00a9:
                out += "(c)";
                break;
              case 0x00a0:
                out += " ";
                break;
              case 0x00b7:
                out += ".";
                break;
              case 0x00b2:
                out += "2";
                break;
              case 0x00b3:
                out += "3";
                break;
              case 0x00b9:
                out += "1";
                break;
              case 0x00bd:
                out += "1/2";
                break;
              case 0x00bc:
                out += "1/4";
                break;
              case 0x00be:
                out += "3/4";
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
              case 0x00c6:
                out += "AE";
                break;
              case 0x00e6:
                out += "ae";
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
              case 0x00d0:
                out += "D";
                break;
              case 0x00f0:
                out += "d";
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
              case 0x00dd:
              case 0x00fd:
              case 0x00ff:
                out += "y";
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

        async function imgToDataURL(src) {
          return new Promise((resolve) => {
            const img = new Image();
            img.crossOrigin = "anonymous";
            img.onload = () => {
              try {
                const canvas = document.createElement("canvas");
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;
                canvas.getContext("2d").drawImage(img, 0, 0);
                resolve(canvas.toDataURL("image/jpeg", 0.92));
              } catch {
                resolve(null);
              }
            };
            img.onerror = () => resolve(null);
            img.src = src;
          });
        }


        /* ── PDF builder ── */
        async function buildPDF() {
                    try {
            
            const doc = new jsPDF({ unit: "mm", format: "a4" });
            const PW = doc.internal.pageSize.getWidth();
            const PH = doc.internal.pageSize.getHeight();
            const M = 18; // page margin
            const CW = PW - M * 2; // content width
            const RED = [200, 16, 46];
            const INK = [13, 13, 13];
            const GRAY = [110, 110, 110];
            const LITE = [248, 246, 243];
            const MID = [185, 185, 185];

            /* ── Collect data from DOM (sp() sanitizes non-ASCII before passing to jsPDF) ── */
            const propName = sp(
              txt("[data-pdf-name]"),
            );
            const place = sp(txt("[data-pdf-location]"));
            const price = sp(txt("[data-pdf-price]"));
            const ref = sp(txt("[data-pdf-ref]"));
            const badges = qa("[data-pdf-badges] > *")
              .map((b) => sp(b.textContent.trim()))
              .join("  |  ");

            const stats = qa("[data-pdf-stat]").map((el) => ({
              val: sp(
                (el.querySelector("[data-pdf-val]")?.textContent || "").trim(),
              ),
              label: sp(
                (
                  el.querySelector("[data-pdf-label]")?.textContent || ""
                ).trim(),
              ),
            }));

            const descParts = qa("[data-pdf-desc] p")
              .map((p) => sp(p.textContent.trim()))
              .filter(Boolean);

            const details = qa("[data-pdf-detail]").map((cell) => ({
              label: sp(
                (
                  cell.querySelector("[data-pdf-label]")?.textContent ||
                  ""
                ).trim(),
              ),
              val: sp(
                (
                  cell.querySelector("[data-pdf-val]")?.textContent || ""
                ).trim(),
              ),
            }));

            const amenities = qa("[data-pdf-amenity]").map((el) =>
              sp(el.textContent.trim()),
            );
            const facilities = qa("[data-pdf-facility]").map((el) =>
              sp(el.textContent.trim()),
            );

            /* ── Try fetching hero image ── */
            const heroSrc =
              q("[data-pdf-hero]")?.src;
            const heroData = heroSrc ? await imgToDataURL(heroSrc) : null;

            /* ── Shared utilities ── */
            const sectionTitle = (doc, text, y) => {
              doc.setFont("helvetica", "bold");
              doc.setFontSize(9);
              doc.setTextColor(...RED);
              doc.text(text.toUpperCase(), M, y);
              doc.setDrawColor(...RED);
              doc.setLineWidth(0.6);
              doc.line(
                M,
                y + 1.5,
                M + doc.getTextWidth(text.toUpperCase()) + 2,
                y + 1.5,
              );
              return y + 7;
            };

            const pageHeader = (doc) => {
              doc.setFillColor(...RED);
              doc.rect(0, 0, PW, 4, "F");
              doc.setFillColor(...INK);
              doc.rect(0, 4, PW, 8, "F");
              doc.setFont("helvetica", "bold");
              doc.setFontSize(7);
              doc.setTextColor(255, 255, 255);
              doc.text("AKAR TOWN", M, 9.5);
              doc.setFont("helvetica", "normal");
              doc.setTextColor(...MID);
              doc.text("Property Datasheet  |  Confidential", PW - M, 9.5, {
                align: "right",
              });
            };

            const pageFooter = (doc, pageNum, total) => {
              const fy = PH - 8;
              doc.setDrawColor(...MID);
              doc.setLineWidth(0.25);
              doc.line(M, fy - 2, PW - M, fy - 2);
              doc.setFont("helvetica", "normal");
              doc.setFontSize(7);
              doc.setTextColor(...GRAY);
              doc.text("AKAR Town Real Estate  |  " + propName, M, fy + 2);
              doc.text("Page " + pageNum + " / " + total, PW - M, fy + 2, {
                align: "right",
              });
              doc.setTextColor(...MID);
              doc.text(
                "All figures are indicative and subject to change without notice.",
                PW / 2,
                fy + 2,
                { align: "center" },
              );
            };

            /* ═══════════════════════════════════════
             PAGE 1 — Cover
          ═══════════════════════════════════════ */

            /* Header bar (0–12 mm), then hero image flush against it */
            pageHeader(doc); // red rect 0–4, dark rect 4–12
            const IMG_Y = 12; // image starts exactly where header ends
            const imgH = 85;
            let y;
            if (heroData) {
              doc.addImage(heroData, "JPEG", 0, IMG_Y, PW, imgH, "", "FAST");
              y = IMG_Y + imgH + 13;
            } else {
              /* No image — dark branded banner as fallback */
              doc.setFillColor(...INK);
              doc.rect(0, IMG_Y, PW, imgH, "F");
              doc.setFillColor(...RED);
              doc.rect(0, IMG_Y, 4, imgH, "F");
              y = IMG_Y + imgH + 13;
            }

            /* Property name */
            doc.setFont("helvetica", "bold");
            doc.setFontSize(26);
            doc.setTextColor(...INK);
            const nameLines = doc.splitTextToSize(propName, CW);
            doc.text(nameLines, M, y);
            y += nameLines.length * 9 + 1;

            /* Location */
            doc.setFont("helvetica", "normal");
            doc.setFontSize(9);
            doc.setTextColor(...GRAY);
            doc.text(place.replace(/\s+/g, " ").trim(), M, y);
            y += 5;

            /* Badges */
            if (badges) {
              doc.setFont("helvetica", "bold");
              doc.setFontSize(7.5);
              doc.setTextColor(...RED);
              doc.text(badges.toUpperCase(), M, y);
              y += 5;
            }

            /* Price & Ref card */
            y += 2;
            doc.setFillColor(...LITE);
            doc.roundedRect(M, y, CW, 20, 2, 2, "F");
            doc.setFillColor(...RED);
            doc.rect(M, y, 3, 20, "F");

            doc.setFont("helvetica", "normal");
            doc.setFontSize(7.5);
            doc.setTextColor(...GRAY);
            doc.text("ASKING PRICE", M + 8, y + 6);
            doc.setFont("helvetica", "bold");
            doc.setFontSize(17);
            doc.setTextColor(...INK);
            doc.text(
              "EGP " + price.replace(/[^\d,\.]/g, "").trim(),
              M + 8,
              y + 15,
            );

            doc.setFont("helvetica", "normal");
            doc.setFontSize(7.5);
            doc.setTextColor(...GRAY);
            doc.text("REF", PW - M - 6, y + 6, { align: "right" });
            doc.setFont("helvetica", "bold");
            doc.setFontSize(10);
            doc.setTextColor(...INK);
            doc.text(ref || "—", PW - M - 6, y + 15, { align: "right" });
            y += 26;

            /* Stats strip */
            const sW = CW / stats.length;
            doc.setFillColor(...INK);
            doc.rect(M, y, CW, 24, "F");
            /* Red accent on left edge */
            doc.setFillColor(...RED);
            doc.rect(M, y, 3, 24, "F");

            stats.forEach((s, i) => {
              const sx = M + 3 + i * sW + sW / 2;
              /* Separator */
              if (i > 0) {
                doc.setDrawColor(50, 50, 50);
                doc.setLineWidth(0.3);
                doc.line(M + 3 + i * sW, y + 4, M + 3 + i * sW, y + 20);
              }
              doc.setFont("helvetica", "bold");
              doc.setFontSize(13);
              doc.setTextColor(255, 255, 255);
              doc.text(s.val, sx, y + 12, { align: "center" });
              doc.setFont("helvetica", "normal");
              doc.setFontSize(6.5);
              doc.setTextColor(...MID);
              doc.text(s.label.toUpperCase(), sx, y + 19, { align: "center" });
            });
            y += 30;

            /* ── About (fill cover with as many paragraphs as fit) ── */
            y = sectionTitle(doc, "About This Property", y);
            doc.setFont("helvetica", "normal");
            doc.setFontSize(9);
            doc.setTextColor(55, 55, 55);
            const lineH1 = 5.5;
            for (let pi0 = 0; pi0 < descParts.length; pi0++) {
              const para0 = (descParts[pi0] || "").trim();
              if (!para0) continue;
              const pLines0 = doc.splitTextToSize(para0, CW);
              const available = Math.floor((PH - y - 20) / lineH1);
              if (available < 2) break;
              if (pLines0.length <= available) {
                pLines0.forEach(function (ln, li) {
                  doc.text(ln, M, y + li * lineH1);
                });
                y += pLines0.length * lineH1;
                if (pi0 < descParts.length - 1 && PH - y - 20 >= lineH1 * 3)
                  y += lineH1 * 0.55;
              } else {
                const show = pLines0.slice(0, available - 1);
                show.forEach((ln, li) => {
                  const last = li === show.length - 1;
                  doc.text(
                    last ? ln.trimEnd() + "..." : ln,
                    M,
                    y + li * lineH1,
                  );
                });
                break;
              }
            }

            /* ═══════════════════════════════════════
             PAGE 2 — Full description + Details
          ═══════════════════════════════════════ */
            doc.addPage();
            pageHeader(doc);
            y = 18;

            /* Full description — paragraph-aware justified rendering */
            y = sectionTitle(doc, "Full Description", y);
            doc.setFont("helvetica", "normal");
            doc.setFontSize(9);
            doc.setTextColor(55, 55, 55);
            const lineH2 = 5.6;
            const bodyFont = () => {
              doc.setFont("helvetica", "normal");
              doc.setFontSize(9);
              doc.setTextColor(55, 55, 55);
            };
            const usableH = PH - 18 - 16; // max content height on a fresh page

            for (let pi = 0; pi < descParts.length; pi++) {
              const para = (descParts[pi] || "").trim();
              if (!para) continue;

              /* Count lines using the SAME function jsPDF will use to render,
               so paraH always matches what doc.text actually draws */
              const lines = doc.splitTextToSize(para, CW);
              const paraH = lines.length * lineH2;

              if (paraH <= usableH) {
                if (y + paraH > PH - 16) {
                  doc.addPage();
                  pageHeader(doc);
                  bodyFont();
                  y = 18;
                }
                lines.forEach(function (ln, li) {
                  doc.text(ln, M, y + li * lineH2);
                });
                y += paraH;
              } else {
                /* Very long paragraph — split across pages */
                for (let li = 0; li < lines.length; li++) {
                  if (y > PH - 16) {
                    doc.addPage();
                    pageHeader(doc);
                    bodyFont();
                    y = 18;
                  }
                  doc.text(lines[li], M, y);
                  y += lineH2;
                }
              }

              if (pi < descParts.length - 1) y += lineH2 * 0.5;
            }
            y += 5;

            /* Property Details table */
            if (y > PH - 50) {
              doc.addPage();
              pageHeader(doc);
              y = 18;
            }
            y = sectionTitle(doc, "Property Details", y);

            const tableBody = [];
            for (let i = 0; i < details.length; i += 2) {
              tableBody.push([
                details[i]?.label || "",
                details[i]?.val || "",
                details[i + 1]?.label || "",
                details[i + 1]?.val || "",
              ]);
            }

            doc.autoTable({
              startY: y,
              margin: { left: M, right: M },
              body: tableBody,
              columnStyles: {
                0: {
                  fontStyle: "normal",
                  textColor: GRAY,
                  fontSize: 8,
                  cellWidth: CW * 0.22,
                },
                1: {
                  fontStyle: "bold",
                  textColor: INK,
                  fontSize: 8.5,
                  cellWidth: CW * 0.28,
                },
                2: {
                  fontStyle: "normal",
                  textColor: GRAY,
                  fontSize: 8,
                  cellWidth: CW * 0.22,
                },
                3: {
                  fontStyle: "bold",
                  textColor: INK,
                  fontSize: 8.5,
                  cellWidth: CW * 0.28,
                },
              },
              styles: {
                cellPadding: { top: 4, bottom: 4, left: 5, right: 4 },
                lineColor: [225, 225, 225],
                lineWidth: 0.25,
                font: "helvetica",
              },
              alternateRowStyles: { fillColor: LITE },
              tableLineColor: [215, 215, 215],
              tableLineWidth: 0.25,
              didDrawPage: () => {
                pageHeader(doc);
              },
            });
            y = doc.lastAutoTable.finalY + 8;

            /* ═══════════════════════════════════════
             PAGE 3 — Amenities + Facilities + CTA
          ═══════════════════════════════════════ */
            if (y > PH - 70) {
              doc.addPage();
              pageHeader(doc);
              y = 18;
            }

            /* Amenities */
            y = sectionTitle(doc, "Amenities & Features", y);
            const colW3 = CW / 3;
            const rowH = 8;
            amenities.forEach((a, i) => {
              const col = i % 3;
              const row = Math.floor(i / 3);
              const ax = M + col * colW3;
              const ay = y + row * rowH;
              if (ay + rowH > PH - 16) return; // overflow guard
              doc.setFillColor(...RED);
              doc.circle(ax + 2, ay - 1.2, 0.9, "F");
              doc.setFont("helvetica", "normal");
              doc.setFontSize(8.5);
              doc.setTextColor(45, 45, 45);
              doc.text(a, ax + 5.5, ay);
            });
            y += Math.ceil(amenities.length / 3) * rowH + 10;

            /* Nearby Facilities */
            if (y > PH - 60) {
              doc.addPage();
              pageHeader(doc);
              y = 18;
            }
            y = sectionTitle(doc, "Nearby Facilities", y);
            facilities.forEach((f, i) => {
              const col = i % 3;
              const row = Math.floor(i / 3);
              const fx = M + col * colW3;
              const fy = y + row * rowH;
              if (fy + rowH > PH - 16) return;
              doc.setFillColor(...RED);
              doc.circle(fx + 2, fy - 1.2, 0.9, "F");
              doc.setFont("helvetica", "normal");
              doc.setFontSize(8.5);
              doc.setTextColor(45, 45, 45);
              doc.text(f, fx + 5.5, fy);
            });
            y += Math.ceil(facilities.length / 3) * rowH + 14;

            /* Contact CTA */
            if (y > PH - 36) {
              doc.addPage();
              pageHeader(doc);
              y = 18;
            }
            doc.setFillColor(...INK);
            doc.roundedRect(M, y, CW, 30, 2.5, 2.5, "F");
            doc.setFillColor(...RED);
            doc.roundedRect(M, y, CW, 30, 2.5, 2.5, "S");
            doc.setLineWidth(0.6);

            doc.setFont("helvetica", "bold");
            doc.setFontSize(12);
            doc.setTextColor(255, 255, 255);
            doc.text("Interested in this property?", M + 10, y + 11);
            doc.setFont("helvetica", "normal");
            doc.setFontSize(8.5);
            doc.setTextColor(200, 200, 200);
            doc.text(
              "Contact our advisors for a private viewing or financing consultation.",
              M + 10,
              y + 18,
            );
            doc.setFont("helvetica", "bold");
            doc.setFontSize(9);
            doc.setTextColor(...RED);
            doc.text(sp(root.dataset.pdfPhone || ""), M + 10, y + 26);
            doc.setTextColor(160, 160, 160);
            doc.text(
              sp([root.dataset.pdfEmail, location.host].filter(Boolean).join("  |  ")),
              PW - M - 6,
              y + 26,
              { align: "right" },
            );

            /* ── Stamp footers on every page ── */
            const total = doc.internal.getNumberOfPages();
            for (let p = 1; p <= total; p++) {
              doc.setPage(p);
              pageFooter(doc, p, total);
            }

            /* ── Download ── */
            const filename =
              (propName || "property")
                .replace(/[^a-z0-9]/gi, "-")
                .toLowerCase() + "-datasheet.pdf";
            doc.save(filename);
          } finally {
            // The loading state is the caller's (Alpine).
          }
        }

        await buildPDF()
}
