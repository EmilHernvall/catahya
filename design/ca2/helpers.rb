module Haml
  module Helpers
    def partial(template, *args)
      template_array = template.to_s.split('/')
      template = template_array[0..-2].join('/') + "/_#{template_array[-1]}"
      options = args.last.is_a?(Hash) ? args.pop : {}
      options.merge!(:layout => false)
      if collection = options.delete(:collection) then
        collection.inject([]) do |buffer, member|
          buffer << haml(:"#{template}", options.merge(:layout =>
          false, :locals => {template_array[-1].to_sym => member}))
        end.join("\n")
      else
        haml(:"#{template}", options)
      end
    end
  end
end

def use_mockup(name)
  if name == "book_reviews"
    @book_reviews = Array.new
  
    @book_reviews.push Hash[ 
      :title => '<b>V</b>ampyrkärlek',
      :preamble => "I 150 år har Elena letat efter sin försvunna dotter Oana, som hon förlorade då de tvingades fly från hämndlystna människor, och nu, i dagens London, verkar hon äntligen ha kommit henne på spåren. Men Oana minns inte längre vem hon är, och Elena måste berätta hela historien, en berättelse som börjar i ett land långt bortom bergen, ett land där de levande...",
      :author => 'Luna Noire',
      :date => '11 juli 2011'
    ]

    @book_reviews.push Hash[ 
      :title => "<b>A</b>ndens kraft",
      :preamble => "Rose Hathaway hoppade skolan efter sin artonårsdag för att åka till Ryssland i syfte att döda den strigoi som innan hans förvandling var hennes lärare och hemliga kärlek, Dimitri. Men hon misslyckades och är nu tillbaka på S:t Vladimir för att ta sin examen, till vilken det inte är speciellt långt kvar. Rose har dock nästan gett upp allt hopp om att få bli sin bästa vä ...",
      :author => "Isblad",
      :date => "7 juli 2011"
    ]
  end
  
  if name == "news_list"
    @news_list = Array.new
    
    @news_list.push Hash[ :date => "11/07", :title => "Swainston slutar skriva" ]
    @news_list.push Hash[ :date => "08/07", :title => "Science fiction-, fantasy- och skräckbibliografi" ]
    @news_list.push Hash[ :date => "05/07", :title => "Tredje utgåvan av Encyclopedia of Science Fiction" ]
    @news_list.push Hash[ :date => "27/06", :title => "Coraline som svensk radioteater" ]
    @news_list.push Hash[ :date => "27/06", :title => "Tudor-skådis till Game of Thrones" ]
    @news_list.push Hash[ :date => "23/06", :title => "Mer Potter på Pottermore" ]
    @news_list.push Hash[ :date => "22/06", :title => "SF-bokhandeln i Göteborg fyller 10" ]
    @news_list.push Hash[ :date => "22/06", :title => "Viktig info om CSC" ]
    @news_list.push Hash[ :date => "20/06", :title => "Eurocon över" ]
  end
end