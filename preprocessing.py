import sys
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory
from Sastrawi.StopWordRemover.StopWordRemoverFactory import StopWordRemoverFactory

stemmer = StemmerFactory().create_stemmer()
stopper = StopWordRemoverFactory().create_stop_word_remover()

sendTitle = sys.argv[1]
sendTitle = sendTitle.split("##")
sendTitle = list(filter(None, sendTitle))
sendTitle = ' '.join(sendTitle)

stem_title = stemmer.stem(sendTitle)
stop_title = stopper.remove(stem_title)

print(stop_title)