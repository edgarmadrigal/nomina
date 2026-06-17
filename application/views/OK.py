import glob, os 
import PyPDF2

ext ="*.pdf"
PATH="/content/drive/MyDrive/4549814932.pdf"

files=[]

for dirpath, dirnames, filenames in os.walk(PATH):
    files +=glob.glob(os.path.join(dirpath,ext))


   #COMENTARIO
   # 
   # 
   # 
   # 
   # 
   # 
   # 
   # 
   # 
   # 
   # 
   #

#Iniciar Listas
po=[]
cliente=[]
noparte=[]
desc=[]
cant=[]
rev=[]
rango=[]
cap=[]
bb=[]
dd=[]
cantpe=[]
fentrega=[]
fembarque=[]
nombres=[]


for idx, filename in enumerate(files):

    pdf_reader= PyPDF2.PdfReader(filename)

    page =pdf_reader.pages[0]

    text =page.extract_text()

    lines =text.split('\n')

    nombres.append(filename)
    

for line in lines:

    if 'order number' in line:

        pos=line.split('order number')[1].strip()
        #print(pos)
        po.append(pos)
data={'PO':po}

df= pd.DataFrame(data)
df