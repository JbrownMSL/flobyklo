#!/usr/bin/env python3
import xml.etree.ElementTree as ET, re, json
from PIL import Image, ImageDraw, ImageFont
NS='{http://schemas.openxmlformats.org/spreadsheetml/2006/main}'
BASE='/tmp/epmaps/xl_extract'
FONT='/usr/share/fonts/dejavu-sans-fonts/DejaVuSans.ttf'
FONTB='/usr/share/fonts/dejavu-sans-fonts/DejaVuSans-Bold.ttf'
ss=[]
for si in ET.parse(BASE+'/xl/sharedStrings.xml').getroot():
    ss.append(''.join(t.text or '' for t in si.iter(NS+'t')))
def colrow(ref):
    m=re.match(r'([A-Z]+)(\d+)',ref); col=0
    for ch in m.group(1): col=col*26+(ord(ch)-64)
    return col-1,int(m.group(2))
def cells(sheet):
    root=ET.parse(f'{BASE}/xl/worksheets/{sheet}.xml').getroot(); d={}
    for c in root.iter(NS+'c'):
        v=c.find(NS+'v')
        if v is None or v.text is None: continue
        val=ss[int(v.text)] if c.get('t')=='s' else v.text
        if val and val.strip(): d[colrow(c.get('r'))]=val.strip()
    return d

FLOORS={1:'sheet5',2:'sheet6',3:'sheet7',4:'sheet8'}
W,H=1400,860; COLS=14; ROWS=27; TITLE=44
cw=W/COLS; ch=(H-TITLE)/ROWS
fnum=ImageFont.truetype(FONTB,15); fname=ImageFont.truetype(FONT,11)
fmark=ImageFont.truetype(FONTB,13); ftitle=ImageFont.truetype(FONTB,26)
coords={}
for fl,sheet in FLOORS.items():
    d=cells(sheet)
    im=Image.new('RGB',(W,H),'white'); dr=ImageDraw.Draw(im)
    # faint grid
    for c in range(COLS+1): dr.line([(c*cw,TITLE),(c*cw,H)],fill='#eef1f5')
    for r in range(ROWS+1): dr.line([(0,TITLE+r*ch),(W,TITLE+r*ch)],fill='#eef1f5')
    title=f'Legacy Cottage Apartments — Floor {fl}'
    dr.text((16,8),title,font=ftitle,fill='#1f3a5f')
    apt={}
    for (col,row),val in d.items():
        x=col*cw; y=TITLE+(row-1)*ch
        if re.fullmatch(r'[1-4]\d\d',val):  # apt number cell
            dr.rectangle([x+2,y+1,x+cw-2,y+ch*3-1],outline='#1f3a5f',width=2,fill='#eaf0f8')
            dr.text((x+cw/2,y+ch/2),val,font=fnum,fill='#1f3a5f',anchor='mm')
            apt[val]=(round((x+cw/2)/W*100,2), round((y+ch*1.5)/H*100,2))
        elif val in ('Stairs','Office'):
            dr.rectangle([x+2,y+1,x+cw-2,y+ch-1],outline='#999',fill='#f0f0f0')
            dr.text((x+cw/2,y+ch/2),val,font=fmark,fill='#666',anchor='mm')
        elif 'Legacy' in val or 'Floor' in val:
            pass  # title already drawn
        elif re.fullmatch(r'[\d\-]{7,}',val.replace(' ','')):
            pass  # phone — skip (privacy on the picture; pin shows it)
        else:  # historical name -> suppressed (current residents come from pins)
            pass
    im.save(f'/tmp/epmaps/floor{fl}.png')
    coords[fl]=apt
    print(f'Floor {fl}: {len(apt)} units rendered')
json.dump(coords,open('/tmp/epmaps/floor_coords.json','w'))
print('wrote floor_coords.json')
