require "rubygems"
require "compass"
require "sinatra"

require "helpers"

configure do
  Compass.add_project_configuration(File.join(Sinatra::Application.root, 'compass.config'))
end

get "/css/raven.css" do
  content_type "text/css", :charset => "utf-8"
  scss(:"../scss/raven", Compass.sass_engine_options)
end

get "/" do
  use_mockup "book_reviews"
  use_mockup "news_list" 
  haml :index
end